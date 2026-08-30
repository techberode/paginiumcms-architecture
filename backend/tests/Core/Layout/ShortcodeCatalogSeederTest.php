<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Layout\Services\ShortcodeCatalogSeeder;
use PaginiumCMS\Core\Layout\Services\ShortcodeDefinitionManager;
use PaginiumCMS\Core\Layout\Services\ShortcodeRegistry;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ShortcodeCatalogSeederTest extends TestCase
{
    private string $baseDir;
    private ShortcodeCatalogSeeder $seeder;
    private ShortcodeDefinitionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_shortcode_seed_' . uniqid('', true);
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

        $this->seeder = new ShortcodeCatalogSeeder(
            $this->manager,
            $registry,
            $this->createMock(ContentCacheService::class)
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testSeedIfEmptyInstallsBundledCatalog(): void
    {
        $this->seeder->seedIfEmpty();

        $names = array_map(static fn (array $item): string => (string) $item['name'], $this->manager->list());
        sort($names);

        $this->assertSame(
            [
                'alert-box',
                'cta-banner',
                'feature-card',
                'feature-grid',
                'landing-hero',
                'pricing-feature',
                'pricing-plan',
                'pricing-table',
                'section-head',
                'showcase-hero',
                'stack-grid',
                'stack-tag',
                'stat-item',
                'stats-row',
                'testimonial',
            ],
            $names
        );
    }

    public function testSeedMissingBundledAddsNewDefinitionsOnly(): void
    {
        $this->seeder->seedIfEmpty();
        $this->seeder->seedMissingBundled();

        $this->assertCount(16, $this->manager->list());
        $this->assertNotEmpty($this->manager->get('landing-hero'));
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
