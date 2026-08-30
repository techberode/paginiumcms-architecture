<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Http\Themes\Services\ThemeCatalogSeeder;
use PaginiumCMS\Http\Themes\Services\ThemeRegistry;
use PHPUnit\Framework\TestCase;

final class ThemeCatalogSeederTest extends TestCase
{
    private string $baseDir;
    private string $packagesRoot;
    private string $themesRoot;
    private ThemeCatalogSeeder $seeder;
    private ThemeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $repoRoot = dirname(__DIR__, 4);
        $this->packagesRoot = $repoRoot . '/backend/resources/theme-packages';
        $this->baseDir = sys_get_temp_dir() . '/pag_theme_catalog_' . uniqid('', true);
        $this->themesRoot = $this->baseDir . '/backend/resources/views/themes';
        mkdir($this->themesRoot, 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $this->registry = new ThemeRegistry($reader, $writer, 'data/themes.json');
        $this->seeder = new ThemeCatalogSeeder($this->registry, $this->packagesRoot, $this->themesRoot);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testSeedMissingBundledInstallsTerminalBreachAndCleanJournal(): void
    {
        $added = $this->seeder->seedMissingBundled();

        $this->assertSame(2, $added);
        $this->assertFileExists($this->themesRoot . '/terminal-breach/theme.json');
        $this->assertFileExists($this->themesRoot . '/clean-journal/theme.json');
        $this->assertNotNull($this->registry->get('terminal-breach'));
        $this->assertNotNull($this->registry->get('clean-journal'));
    }

    public function testSeedMissingBundledIsIdempotent(): void
    {
        $this->seeder->seedMissingBundled();
        $this->assertSame(0, $this->seeder->seedMissingBundled());
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
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
