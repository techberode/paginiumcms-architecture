<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\Layout\Services\ShortcodeDefinitionManager;
use PaginiumCMS\Core\Layout\Services\ShortcodeExpanderService;
use PaginiumCMS\Core\Layout\Services\ShortcodeRegistry;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ShortcodeExpanderServiceTest extends TestCase
{
    private string $baseDir;
    private ShortcodeDefinitionManager $manager;
    private ShortcodeExpanderService $expander;
    private ContentBodyRenderer $bodyRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_shortcode_exp_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new ShortcodeRegistry($reader, $writer, 'data/shortcodes/registry.json');

        $settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $policyEngine = new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner());

        $this->manager = new ShortcodeDefinitionManager(
            new ShortcodeDefinitionPolicy(),
            $policyEngine,
            $registry,
            $reader,
            $writer
        );

        $json = file_get_contents(__DIR__ . '/../../Fixtures/hostile/shortcodes/safe-alert.json');
        $this->assertIsString($json);
        $this->manager->save('alert-box', $json);

        $this->expander = new ShortcodeExpanderService(
            $registry,
            $reader,
            new ContentSecuritySanitizer($settings)
        );

        $markdown = $this->createMock(MarkdownContentParser::class);
        $markdown->method('parse')->willReturnCallback(static fn (string $body): string => '<p>' . htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>');

        $this->bodyRenderer = new ContentBodyRenderer(
            $markdown,
            new TiptapHtmlRenderer(),
            new ContentSecuritySanitizer($settings),
            $this->expander
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testExpandsPairedShortcode(): void
    {
        $result = $this->expander->expand('Before [alert-box tone="info"]Hello[/alert-box] after');

        $this->assertStringContainsString('pg-alert', $result);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringNotContainsString('[alert-box', $result);
    }

    public function testBodyRendererExpandsBeforeMarkdownParse(): void
    {
        $html = $this->bodyRenderer->resolveHtml('[alert-box tone="warn"]Warn body[/alert-box]', 'markdown');

        $this->assertStringContainsString('pg-alert', $html);
        $this->assertStringContainsString('Warn body', $html);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
