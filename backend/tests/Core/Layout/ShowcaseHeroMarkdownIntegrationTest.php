<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\Layout\Services\ShortcodeCatalogSeeder;
use PaginiumCMS\Core\Layout\Services\ShortcodeDefinitionManager;
use PaginiumCMS\Core\Layout\Services\ShortcodeExpanderService;
use PaginiumCMS\Core\Layout\Services\ShortcodeRegistry;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ShowcaseHeroMarkdownIntegrationTest extends TestCase
{
    private ContentBodyRenderer $bodyRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        $baseDir = sys_get_temp_dir() . '/pag_showcase_md_' . uniqid('', true);
        mkdir($baseDir . '/data', 0777, true);

        $validator = new FileValidator($baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new ShortcodeRegistry($reader, $writer, 'data/shortcodes/registry.json');
        $settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($baseDir),
            new Validator(),
            'data/settings.json'
        );
        $policyEngine = new CodePolicyEngine($settings, new SyntaxChecker(), new SecurityScanner());
        $manager = new ShortcodeDefinitionManager(
            new ShortcodeDefinitionPolicy(),
            $policyEngine,
            $registry,
            $reader,
            $writer
        );
        (new ShortcodeCatalogSeeder($manager, $registry, $this->createMock(ContentCacheService::class)))->seedIfEmpty();

        $expander = new ShortcodeExpanderService($registry, $reader, new ContentSecuritySanitizer($settings));
        $markdown = new MarkdownContentParser();
        $this->bodyRenderer = new ContentBodyRenderer(
            $markdown,
            new TiptapHtmlRenderer(),
            new ContentSecuritySanitizer($settings),
            $expander
        );
    }

    public function testSelfClosingShowcaseHeroSurvivesMarkdownAfterImage(): void
    {
        $shortcode = '[showcase-hero badge="HYBRID" title="Obsah pod kontrolou" subtitle="Sub" '
            . 'terminal="curl /api/health && open /admin" cta="Go" href="#platform-overview" '
            . 'cta2="Blog" href2="/blog"/]';

        $body = "![hero](https://example.test/media/hero.png)\n\n" . $shortcode . "\n\n[stats-row][/stats-row]";

        $html = $this->bodyRenderer->resolveHtml($body, 'markdown');

        $this->assertStringContainsString('pg-showcase-hero', $html);
        $this->assertStringContainsString('Obsah pod kontrolou', $html);
        $this->assertStringContainsString('pg-stats', $html);
        $this->assertStringNotContainsString('[showcase-hero', $html);
    }
}
