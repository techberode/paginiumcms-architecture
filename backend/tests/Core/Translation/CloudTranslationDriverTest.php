<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Translation;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Translation\Contracts\TranslationHttpTransport;
use PaginiumCMS\Core\Translation\Drivers\DeepLTranslationDriver;
use PaginiumCMS\Core\Translation\Drivers\GoogleTranslationDriver;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Core\Translation\Services\TranslationCredentialResolver;
use PaginiumCMS\Core\Translation\Services\TranslationFixedHostPolicy;
use PaginiumCMS\Core\Translation\Services\TranslationSettings;
use PHPUnit\Framework\TestCase;

final class CloudTranslationDriverTest extends TestCase
{
    public function testDeepLUsesFreeHostForFxKey(): void
    {
        $transport = new CloudRecordingTransport([
            'translations' => [['text' => 'Hello']],
        ]);
        $driver = $this->deepl(['deeplApiKey' => 'abc:fx'], $transport);

        $result = $driver->translate('Ahoj', 'sk', 'en');
        $this->assertSame('Hello', $result['text']);
        $this->assertSame('https://api-free.deepl.com/v2/translate', $transport->lastUrl);
        $this->assertArrayHasKey('Authorization', $transport->lastHeaders);
        $this->assertStringNotContainsString('abc:fx', json_encode($transport->lastBody) ?: '');
    }

    public function testGoogleUsesFixedHost(): void
    {
        $transport = new CloudRecordingTransport([
            'data' => ['translations' => [['translatedText' => 'Hello']]],
        ]);
        $driver = $this->google(['googleApiKey' => 'gkey'], $transport);

        $result = $driver->translate('Ahoj', 'sk', 'en');
        $this->assertSame('Hello', $result['text']);
        $this->assertStringStartsWith('https://translation.googleapis.com/language/translate/v2', (string) $transport->lastUrl);
    }

    public function testFixedHostPolicyRejectsArbitraryEndpoint(): void
    {
        $policy = new TranslationFixedHostPolicy();
        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('not allowed');
        $policy->assertProviderUrl('deepl', 'https://evil.example/v2/translate');
    }

    public function testGoogleRejectsArbitraryHost(): void
    {
        $policy = new TranslationFixedHostPolicy();
        $this->expectException(TranslationException::class);
        $policy->assertProviderUrl('google', 'https://example.com/translate');
    }

    /**
     * @param array<string, mixed> $group
     */
    private function deepl(array $group, TranslationHttpTransport $transport): DeepLTranslationDriver
    {
        $settings = $this->settings($group);

        return new DeepLTranslationDriver(
            $settings,
            $transport,
            new TranslationCredentialResolver($settings),
            new TranslationFixedHostPolicy()
        );
    }

    /**
     * @param array<string, mixed> $group
     */
    private function google(array $group, TranslationHttpTransport $transport): GoogleTranslationDriver
    {
        $settings = $this->settings($group);

        return new GoogleTranslationDriver(
            $settings,
            $transport,
            new TranslationCredentialResolver($settings),
            new TranslationFixedHostPolicy()
        );
    }

    /**
     * @param array<string, mixed> $group
     */
    private function settings(array $group): TranslationSettings
    {
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->willReturn($group);

        return new TranslationSettings($repo);
    }
}

final class CloudRecordingTransport implements TranslationHttpTransport
{
    public ?string $lastUrl = null;

    /** @var array<string, mixed>|null */
    public ?array $lastBody = null;

    /** @var array<string, string> */
    public array $lastHeaders = [];

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        private array $response,
    ) {
    }

    public function request(string $method, string $url, ?array $body, int $timeoutSeconds, array $headers = []): array
    {
        $this->lastUrl = $url;
        $this->lastBody = $body;
        $this->lastHeaders = $headers;

        return $this->response;
    }
}
