<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Translation;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Translation\Contracts\TranslationHttpTransport;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Core\Translation\Services\TranslationPlaceholderGuard;
use PaginiumCMS\Core\Translation\Services\TranslationProposalStore;
use PaginiumCMS\Core\Translation\Services\TranslationCredentialResolver;
use PaginiumCMS\Core\Translation\Services\TranslationFixedHostPolicy;
use PaginiumCMS\Core\Translation\Services\TranslationProviderRegistry;
use PaginiumCMS\Core\Translation\Services\TranslationQuotaStore;
use PaginiumCMS\Core\Translation\Services\TranslationService;
use PaginiumCMS\Core\Translation\Services\TranslationSettings;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PHPUnit\Framework\TestCase;

final class TranslationServiceTest extends TestCase
{
    private string $baseDir;

    private ?Page $lastSaved = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_tr_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $this->lastSaved = null;
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testDisabledDoesNotCallProvider(): void
    {
        $transport = new RecordingTranslationTransport();
        $page = $this->slovakPage();
        $service = $this->makeService($transport, ['enabled' => false, 'provider' => 'none'], $page);

        try {
            $service->propose('page', 'about', [
                'sourceLocale' => 'sk',
                'targetLocales' => ['en'],
            ], 'user-1', 'ed@example.com');
            $this->fail('Expected disabled exception');
        } catch (TranslationException $e) {
            $this->assertSame('DISABLED', $e->errorCode);
            $this->assertSame(503, $e->httpStatus);
        }

        $this->assertSame([], $transport->calls);
    }

    public function testProposeAndApplyWritesDraftOnly(): void
    {
        $transport = new RecordingTranslationTransport();
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'libretranslate',
            'baseUrl' => 'https://translate.example.com',
            'apiKey' => 'secret-key',
        ], $page);

        $proposal = $service->propose('page', 'about', [
            'sourceLocale' => 'sk',
            'targetLocales' => ['en'],
            'fields' => ['title', 'body'],
        ], 'user-1', 'ed@example.com');

        $this->assertSame('ok', $proposal['locales']['en']['status']);
        $this->assertSame('Hello', $proposal['locales']['en']['fields']['title']);
        $this->assertNotSame('', $proposal['id']);
        $this->assertNotEmpty($transport->calls);

        $result = $service->apply((string) $proposal['id'], 'user-1', 'ed@example.com');
        $this->assertFalse($result['published']);
        $this->assertSame('draft', $result['status']);
        $this->assertSame(['en'], $result['appliedLocales']);
        $this->assertInstanceOf(Page::class, $this->lastSaved);
        $this->assertSame('draft', $this->lastSaved->getFrontMatter()['localeStatus']['en']);
        $this->assertSame('published', $this->lastSaved->getFrontMatter()['localeStatus']['sk']);
        $this->assertSame('Hello', $this->lastSaved->getFrontMatter()['localizedContent']['en']['title']);

        $audit = file_get_contents($this->baseDir . '/data/security/audit_events.json') ?: '';
        $this->assertStringContainsString('content.translated', $audit);
        $this->assertStringNotContainsString('Ahoj svet', $audit);
        $this->assertStringNotContainsString('secret-key', $audit);
    }

    public function testApplyConflictsWhenSourceRevisionChanges(): void
    {
        $transport = new RecordingTranslationTransport();
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'libretranslate',
            'baseUrl' => 'https://translate.example.com',
        ], $page);

        $proposal = $service->propose('page', 'about', [
            'sourceLocale' => 'sk',
            'targetLocales' => ['en'],
            'fields' => ['title'],
        ], 'user-1', null);

        $page->setContent('changed');

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('revision');
        $service->apply((string) $proposal['id'], 'user-1', null);
    }

    public function testQuotaExceededDoesNotApply(): void
    {
        $transport = new RecordingTranslationTransport();
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'libretranslate',
            'baseUrl' => 'https://translate.example.com',
            'dailyCharLimit' => 3,
        ], $page);

        try {
            $service->propose('page', 'about', [
                'sourceLocale' => 'sk',
                'targetLocales' => ['en'],
                'fields' => ['title', 'body'],
            ], 'user-1', null);
            $this->fail('Expected quota exception');
        } catch (TranslationException $e) {
            $this->assertSame('QUOTA_EXCEEDED', $e->errorCode);
            $this->assertSame(429, $e->httpStatus);
        }

        $this->assertNull($this->lastSaved);
    }

    public function testInvalidProviderResponseIsRejected(): void
    {
        $transport = new RecordingTranslationTransport();
        $transport->response = ['oops' => true];
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'libretranslate',
            'baseUrl' => 'https://translate.example.com',
        ], $page);

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('No locales were translated');
        $service->propose('page', 'about', [
            'sourceLocale' => 'sk',
            'targetLocales' => ['en'],
            'fields' => ['title'],
        ], 'user-1', null);
    }

    public function testPrivateUrlIsBlockedInProductionGuard(): void
    {
        $transport = new RecordingTranslationTransport();
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'libretranslate',
            'baseUrl' => 'https://192.168.1.10:5000',
        ], $page, new OutboundUrlGuard(false, false));

        try {
            $service->propose('page', 'about', [
                'sourceLocale' => 'sk',
                'targetLocales' => ['en'],
            ], 'user-1', null);
            $this->fail('Expected SSRF block');
        } catch (TranslationException $e) {
            $this->assertSame('SSRF_BLOCKED', $e->errorCode);
        }
        $this->assertSame([], $transport->calls);
    }

    public function testDeeplProposalUsesFixedHostAndWriteOnlyKey(): void
    {
        $transport = new RecordingTranslationTransport();
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'deepl',
            'deeplApiKey' => 'secret-deepl-key:fx',
        ], $page);

        $proposal = $service->propose('page', 'about', [
            'sourceLocale' => 'sk',
            'targetLocales' => ['en'],
            'fields' => ['title'],
        ], 'user-1', null);

        $this->assertSame('ok', $proposal['locales']['en']['status']);
        $this->assertSame('deepl', $proposal['locales']['en']['provider']);
        $this->assertStringContainsString('api-free.deepl.com', (string) $transport->calls[0]['url']);
        $audit = file_get_contents($this->baseDir . '/data/security/audit_events.json') ?: '';
        $this->assertStringNotContainsString('secret-deepl-key', $audit);
    }

    public function testFailoverUsesSecondaryProviderAndAudits(): void
    {
        $transport = new RecordingTranslationTransport();
        $transport->failDeepl = true;
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'deepl',
            'deeplApiKey' => 'deepl-key:fx',
            'fallbackEnabled' => true,
            'fallbackProvider' => 'libretranslate',
            'baseUrl' => 'https://translate.example.com',
        ], $page);

        $proposal = $service->propose('page', 'about', [
            'sourceLocale' => 'sk',
            'targetLocales' => ['en'],
            'fields' => ['title'],
        ], 'user-1', null);

        $this->assertSame('libretranslate', $proposal['locales']['en']['provider']);
        $audit = file_get_contents($this->baseDir . '/data/security/audit_events.json') ?: '';
        $this->assertStringContainsString('content.translation_fallback', $audit);
        $this->assertStringNotContainsString('deepl-key', $audit);
    }

    public function testAuthFailureDoesNotFailover(): void
    {
        $transport = new RecordingTranslationTransport();
        $transport->failAuth = true;
        $page = $this->slovakPage();
        $service = $this->makeService($transport, [
            'enabled' => true,
            'provider' => 'google',
            'googleApiKey' => 'google-secret',
            'fallbackEnabled' => true,
            'fallbackProvider' => 'libretranslate',
            'baseUrl' => 'https://translate.example.com',
        ], $page);

        try {
            $service->propose('page', 'about', [
                'sourceLocale' => 'sk',
                'targetLocales' => ['en'],
                'fields' => ['title'],
            ], 'user-1', null);
            $this->fail('Expected auth failure without fallback');
        } catch (TranslationException $e) {
            $this->assertContains($e->errorCode, ['AUTH_FAILED', 'NO_RESULT']);
        }

        $auditPath = $this->baseDir . '/data/security/audit_events.json';
        $audit = is_file($auditPath) ? (string) file_get_contents($auditPath) : '';
        $this->assertStringNotContainsString('translation_fallback', $audit);
        $libreCalls = array_filter($transport->calls, static fn (array $call): bool => str_contains((string) $call['url'], 'translate.example.com'));
        $this->assertSame([], $libreCalls);
    }

    /**
     * @param array<string, mixed> $translation
     */
    private function makeService(
        RecordingTranslationTransport $transport,
        array $translation,
        Page $page,
        ?OutboundUrlGuard $guard = null,
    ): TranslationService {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(
            static fn (string $group): array => $group === 'translation' ? $translation : []
        );
        $settings->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $key === 'general.language' ? 'sk' : $default
        );

        $repo = $this->createMock(ContentRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn($page);
        $repo->method('save')->willReturnCallback(function (object $content): void {
            $this->lastSaved = $content instanceof Page ? $content : null;
        });

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $translationSettings = new TranslationSettings($settings);
        $normalizer = new LocalizedContentNormalizer($settings);

        return new TranslationService(
            $translationSettings,
            new TranslationProviderRegistry(
                $translationSettings,
                $transport,
                new TranslationCredentialResolver($translationSettings),
                new TranslationFixedHostPolicy()
            ),
            new TranslationPlaceholderGuard(),
            new TranslationProposalStore($reader, $writer),
            new TranslationQuotaStore($reader, $writer, $translationSettings),
            $repo,
            $normalizer,
            new LocalizedContentWriter($normalizer),
            new ContentRevision(),
            new SecurityAuditStore($reader),
            $guard
        );
    }

    private function slovakPage(): Page
    {
        $page = new Page();
        $page->setPath('pages/about.json');
        $page->setSlug('about');
        $page->setTitle('Ahoj');
        $page->setContent('Ahoj svet');
        $page->setStatus('published');
        $page->setFrontMatter([
            'schemaVersion' => 2,
            'defaultLocale' => 'sk',
            'slug' => 'about',
            'status' => 'published',
            'localizedContent' => [
                'sk' => [
                    'title' => 'Ahoj',
                    'body' => 'Ahoj svet',
                    'seo' => ['title' => '', 'description' => '', 'canonical' => '', 'ogImage' => '', 'noIndex' => false],
                ],
            ],
            'localeStatus' => ['sk' => 'published'],
        ]);

        return $page;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}

final class RecordingTranslationTransport implements TranslationHttpTransport
{
    /** @var list<array{method: string, url: string, body: array<string, mixed>|null}> */
    public array $calls = [];

    /** @var array<string, mixed> */
    public array $response = ['translatedText' => 'Hello'];

    public bool $failDeepl = false;

    public bool $failAuth = false;

    public function request(string $method, string $url, ?array $body, int $timeoutSeconds, array $headers = []): array
    {
        $this->calls[] = ['method' => $method, 'url' => $url, 'body' => $body, 'headers' => $headers];
        if ($this->failAuth) {
            throw new TranslationException('Translation provider authentication failed', 502, 'AUTH_FAILED');
        }
        if ($this->failDeepl && str_contains($url, 'deepl.com')) {
            throw new TranslationException('Translation provider unavailable', 503, 'PROVIDER_UNAVAILABLE');
        }
        if (($this->response['oops'] ?? false) === true) {
            return [];
        }
        if (str_contains($url, 'deepl.com')) {
            $parts = is_array($body['text'] ?? null) ? $body['text'] : [];
            $source = (string) ($parts[0] ?? '');

            return ['translations' => [['text' => $source === 'Ahoj svet' ? 'Hello world' : 'Hello']]];
        }
        if (str_contains($url, 'googleapis.com')) {
            $q = is_array($body) ? (string) ($body['q'] ?? '') : '';

            return ['data' => ['translations' => [['translatedText' => $q === 'Ahoj svet' ? 'Hello world' : 'Hello']]]];
        }
        $q = is_array($body) ? (string) ($body['q'] ?? '') : '';
        $text = $q === 'Ahoj svet' ? 'Hello world' : (string) ($this->response['translatedText'] ?? 'Hello');

        return ['translatedText' => $text];
    }
}
