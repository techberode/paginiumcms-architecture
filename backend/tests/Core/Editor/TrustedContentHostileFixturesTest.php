<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Core\Editor\Services\EditorContentValidator;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Core\Editor\Services\ExternalEmbedContentService;
use PaginiumCMS\Core\Editor\Services\ExternalEmbedShortcode;
use PaginiumCMS\Core\Editor\Services\HtmlSafeShortcode;
use PaginiumCMS\Core\Editor\Services\TrustedHtmlContentService;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\Security\Services\TrustedHtmlPurifier;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Hostile Markdown fixtures for trusted HTML / embed shortcodes (It.91d).
 */
final class TrustedContentHostileFixturesTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __DIR__ . '/../../Fixtures/hostile/trusted-content';
    }

    #[DataProvider('htmlSafeHostileFixtures')]
    public function testPurifierStripsHostileHtmlSafeBodies(string $fixture): void
    {
        $markdown = $this->loadFixture($fixture);
        $service = $this->trustedHtmlService(trustedEnabled: true, permitted: true);
        $normalized = $service->normalizeMarkdown($markdown);

        $this->assertStringNotContainsString('<script', strtolower($normalized));
        $this->assertStringNotContainsString('onload=', strtolower($normalized));
        $this->assertStringNotContainsString('javascript:', strtolower($normalized));
        $this->assertStringContainsString(':::html-safe', $normalized);
    }

    /**
     * @return list<array{string}>
     */
    public static function htmlSafeHostileFixtures(): array
    {
        return [
            ['html-safe-script-onload.md'],
            ['html-safe-javascript-href.md'],
        ];
    }

    #[DataProvider('embedHostileFixtures')]
    public function testEmbedHostileFixturesFailValidation(string $fixture): void
    {
        $markdown = $this->loadFixture($fixture);
        $embed = $this->externalEmbedService(permitted: true);
        $error = $embed->validateMarkdown($markdown, new User());

        $this->assertNotNull($error);
    }

    /**
     * @return list<array{string}>
     */
    public static function embedHostileFixtures(): array
    {
        return [
            ['embed-evil-provider.md'],
            ['embed-youtube-id-injection.md'],
        ];
    }

    public function testRawHtmlOutsideBlockRejected(): void
    {
        $markdown = $this->loadFixture('raw-html-outside-block.md');
        $validator = $this->editorValidator(trustedEnabled: true, trustedPermitted: true, embedPermitted: true);

        $error = $validator->validate('article', [
            'content' => $markdown,
            'contentFormat' => 'markdown',
            'editorProfile' => 'developer',
        ]);

        $this->assertSame('Markdown obsah nesmie obsahovať raw HTML tagy.', $error);
    }

    public function testNormalizedHtmlSafePublicParseContainsNoScript(): void
    {
        $markdown = $this->loadFixture('html-safe-script-onload.md');
        $service = $this->trustedHtmlService(trustedEnabled: true, permitted: true);
        $normalized = $service->normalizeMarkdown($markdown);

        $html = (new MarkdownContentParser())->parse($normalized);
        $lower = strtolower($html);

        $this->assertStringContainsString('paginium-html-safe', $lower);
        $this->assertStringNotContainsString('<script', $lower);
        $this->assertStringNotContainsString('onload=', $lower);
    }

    public function testEmbedHostileFixtureProducesNoIframeOnExpand(): void
    {
        $markdown = $this->loadFixture('embed-evil-provider.md');
        $expanded = (new ExternalEmbedShortcode())->expand($markdown);

        $this->assertStringNotContainsString('<iframe', strtolower($expanded));
    }

    private function loadFixture(string $name): string
    {
        $path = $this->fixturesDir . '/' . $name;
        $content = file_get_contents($path);
        $this->assertIsString($content);

        return $content;
    }

    private function trustedHtmlService(bool $trustedEnabled, bool $permitted): TrustedHtmlContentService
    {
        $settings = $this->settingsRepository([
            'trustedHtmlEnabled' => $trustedEnabled,
            'embedProvidersEnabled' => 'youtube,vimeo',
        ]);
        $auth = $this->createMock(AuthorizationInterface::class);
        $auth->method('hasPermission')->willReturnCallback(
            static fn (?User $user, string $permission): bool => $permitted && $user !== null
        );

        return new TrustedHtmlContentService($settings, $auth, new TrustedHtmlPurifier($settings));
    }

    private function externalEmbedService(bool $permitted): ExternalEmbedContentService
    {
        $settings = $this->settingsRepository([
            'trustedHtmlEnabled' => true,
            'embedProvidersEnabled' => 'youtube,vimeo',
        ]);
        $auth = $this->createMock(AuthorizationInterface::class);
        $auth->method('hasPermission')->willReturnCallback(
            static fn (?User $user, string $permission): bool => $permitted && $user !== null
        );

        return new ExternalEmbedContentService($settings, $auth);
    }

    private function editorValidator(
        bool $trustedEnabled,
        bool $trustedPermitted,
        bool $embedPermitted
    ): EditorContentValidator {
        $settings = $this->settingsRepository([
            'trustedHtmlEnabled' => $trustedEnabled,
            'embedProvidersEnabled' => 'youtube,vimeo',
        ]);
        $plugins = $this->createMock(PluginManagerInterface::class);
        $plugins->method('listEnabledEditorComponents')->willReturn([]);
        $components = new EditorComponentRegistry($plugins);
        $auth = $this->createMock(AuthorizationInterface::class);
        $auth->method('hasPermission')->willReturnCallback(
            static function (?User $user, string $permission) use ($trustedPermitted, $embedPermitted): bool {
                if ($user === null) {
                    return false;
                }

                return match ($permission) {
                    TrustedHtmlContentService::PERMISSION_TRUSTED_HTML => $trustedPermitted,
                    ExternalEmbedContentService::PERMISSION_EMBED_EXTERNAL => $embedPermitted,
                    default => false,
                };
            }
        );

        return new EditorContentValidator(
            new EditorProfileService($settings, $components),
            $components,
            new TrustedHtmlContentService($settings, $auth, new TrustedHtmlPurifier($settings)),
            new ExternalEmbedContentService($settings, $auth),
            new HtmlSafeShortcode(),
        );
    }

    /**
     * @param array<string, mixed> $editorOverrides
     */
    private function settingsRepository(array $editorOverrides = []): SettingsRepository
    {
        $baseDir = sys_get_temp_dir() . '/paginium_trusted_hostile_' . uniqid('', true);
        mkdir($baseDir . '/data', 0777, true);

        $validator = new FileValidator($baseDir);
        $settings = new SettingsRepository(
            new FileWriter($validator),
            StorageTestHelper::localStorage($baseDir),
            new Validator(),
            'data/settings.json'
        );
        $settings->setGroup('editor', array_merge($settings->group('editor'), $editorOverrides));

        return $settings;
    }
}
