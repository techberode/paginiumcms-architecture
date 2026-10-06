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
                'coming-soon',
                'cta-banner',
                'document-link',
                'faq-item',
                'faq-list',
                'feature-card',
                'feature-gallery',
                'feature-grid',
                'gallery-carousel',
                'landing-hero',
                'latest-articles',
                'link-chip',
                'link-row',
                'media-gallery',
                'pricing-feature',
                'pricing-plan',
                'pricing-table',
                'section-band',
                'section-head',
                'showcase-hero',
                'stack-grid',
                'stack-tag',
                'staff-card',
                'staff-team',
                'stat-item',
                'stats-row',
                'testimonial',
                'visual-frame',
            ],
            $names
        );
    }

    public function testSeedMissingBundledAddsNewDefinitionsOnly(): void
    {
        $this->seeder->seedIfEmpty();
        $this->seeder->seedMissingBundled();

        $this->assertCount(29, $this->manager->list());
        $this->assertNotEmpty($this->manager->get('landing-hero'));
        $this->assertNotEmpty($this->manager->get('coming-soon'));
        $this->assertNotEmpty($this->manager->get('feature-gallery'));
    }

    public function testSeedMissingBundledUpgradesStatsRowToV2Animate(): void
    {
        $this->seeder->seedIfEmpty();
        $this->manager->save('stats-row', json_encode([
            'name' => 'stats-row',
            'version' => 1,
            'attrs' => [],
            'expand' => '<div>{{content}}</div>',
        ], JSON_THROW_ON_ERROR));

        $this->seeder->seedMissingBundled();

        $loaded = $this->manager->get('stats-row');
        $definition = $loaded['definition'];
        $this->assertIsArray($definition);
        $this->assertSame(2, (int) ($definition['version'] ?? 0));
        $this->assertArrayHasKey('animate', $definition['attrs']);
    }

    public function testSeedMissingBundledUpgradesPricingTableToV2BillingToggle(): void
    {
        $this->seeder->seedIfEmpty();
        $this->manager->save('pricing-table', json_encode([
            'name' => 'pricing-table',
            'version' => 1,
            'attrs' => ['columns' => ['type' => 'enum', 'options' => ['2', '3']]],
            'expand' => '<div>{{content}}</div>',
        ], JSON_THROW_ON_ERROR));

        $this->seeder->seedMissingBundled();

        $loaded = $this->manager->get('pricing-table');
        $definition = $loaded['definition'];
        $this->assertIsArray($definition);
        $this->assertSame(2, (int) ($definition['version'] ?? 0));
        $this->assertArrayHasKey('billing-toggle', $definition['attrs']);
    }

    public function testSeedMissingBundledUpgradesSectionBandToV2EffectAttrs(): void
    {
        $this->seeder->seedIfEmpty();
        $this->manager->save('section-band', json_encode([
            'name' => 'section-band',
            'version' => 1,
            'attrs' => [
                'anchor' => ['type' => 'string'],
            ],
            'expand' => '<section class="pg-section-band"><div class="pg-section-band__inner">{{content}}</div></section>',
        ], JSON_THROW_ON_ERROR));

        $this->seeder->seedMissingBundled();

        $loaded = $this->manager->get('section-band');
        $definition = $loaded['definition'];
        $this->assertIsArray($definition);
        $this->assertSame(2, (int) ($definition['version'] ?? 0));
        $this->assertArrayHasKey('hover-effect', $definition['attrs']);
    }

    public function testSeedMissingBundledUpgradesFeatureGalleryToV2LayoutAttrs(): void
    {
        $this->seeder->seedIfEmpty();
        $this->manager->save('feature-gallery', json_encode([
            'name' => 'feature-gallery',
            'version' => 1,
            'attrs' => [
                'title' => ['type' => 'string'],
                'tag' => ['type' => 'string'],
            ],
            'expand' => '<section class="pg-feature-gallery" data-tag="{{tag}}" data-title="{{title}}"></section>',
        ], JSON_THROW_ON_ERROR));

        $this->seeder->seedMissingBundled();

        $loaded = $this->manager->get('feature-gallery');
        $definition = $loaded['definition'];
        $this->assertIsArray($definition);
        $this->assertSame(2, (int) ($definition['version'] ?? 0));
        $this->assertArrayHasKey('layout', $definition['attrs']);
        $this->assertSame('enum', $definition['attrs']['layout']['type']);
    }

    public function testSeedMissingBundledUpgradesLandingHeroToMediaAttrs(): void
    {
        $this->seeder->seedIfEmpty();
        $this->manager->save('landing-hero', json_encode([
            'name' => 'landing-hero',
            'version' => 1,
            'attrs' => [
                'title' => ['type' => 'string'],
            ],
            'expand' => '<section class="pg-hero"><div class="pg-hero-inner"><h1 class="pg-hero-title">{{title}}</h1></div></section>',
        ], JSON_THROW_ON_ERROR));

        $this->seeder->seedMissingBundled();

        $loaded = $this->manager->get('landing-hero');
        $definition = $loaded['definition'];
        $this->assertIsArray($definition);
        $this->assertSame(3, (int) ($definition['version'] ?? 0));
        $this->assertArrayHasKey('image', $definition['attrs']);
        $this->assertSame('media', $definition['attrs']['image']['type']);
        $this->assertSame('video', $definition['attrs']['src']['accept']);
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
