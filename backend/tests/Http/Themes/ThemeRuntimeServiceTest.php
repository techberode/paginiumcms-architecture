<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Themes\Models\ThemeRecord;
use PaginiumCMS\Http\Themes\Services\ThemeRegistry;
use PaginiumCMS\Http\Themes\Services\ThemeRuntimeService;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class ThemeRuntimeServiceTest extends TestCase
{
    private string $baseDir;
    private string $themesRoot;
    private ThemeRuntimeService $runtime;
    private SettingsRepository $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_theme_runtime_' . uniqid('', true);
        $this->themesRoot = $this->baseDir . '/backend/resources/views/themes';
        mkdir($this->themesRoot, 0777, true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $registry = new ThemeRegistry($reader, $writer, 'data/themes.json');

        $this->settings = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );

        $cache = new ContentCacheService(new CacheManager(new MemoryDriver()));

        $this->runtime = new ThemeRuntimeService(
            $this->settings,
            $registry,
            $cache,
            $this->themesRoot
        );

        $this->installTheme('clean-journal');
        $registry->upsert(new ThemeRecord('clean-journal', false, '2026-08-30T10:00:00+00:00'));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testResolveActiveThemeIdDefaultsToCore(): void
    {
        $this->assertSame(ThemeRuntimeService::CORE_THEME_ID, $this->runtime->resolveActiveThemeId());
    }

    public function testActivateThemePersistsSettingsAndMarksRegistryEnabled(): void
    {
        $result = $this->runtime->activate('clean-journal');

        $this->assertSame('clean-journal', $result['activeThemeId']);
        $this->assertSame(ThemeRuntimeService::CORE_THEME_ID, $result['previousThemeId']);
        $this->assertSame('clean-journal', $this->runtime->resolveActiveThemeId());

        $appearance = $this->settings->group('appearance');
        $this->assertSame('clean-journal', $appearance['activeThemeId']);
        $this->assertSame(ThemeRuntimeService::CORE_THEME_ID, $appearance['previousThemeId']);
    }

    public function testDeactivateRestoresCoreTheme(): void
    {
        $this->runtime->activate('clean-journal');
        $result = $this->runtime->deactivate();

        $this->assertSame(ThemeRuntimeService::CORE_THEME_ID, $result['activeThemeId']);
        $this->assertSame(ThemeRuntimeService::CORE_THEME_ID, $this->runtime->resolveActiveThemeId());
    }

    public function testResolveFallsBackWhenThemeMissingOnDisk(): void
    {
        $this->settings->setGroup('appearance', ['activeThemeId' => 'ghost-theme']);

        $this->assertSame(ThemeRuntimeService::CORE_THEME_ID, $this->runtime->resolveActiveThemeId());
    }

    public function testAssertNotActiveBlocksUninstall(): void
    {
        $this->runtime->activate('clean-journal');

        $this->expectException(\RuntimeException::class);
        $this->runtime->assertNotActive('clean-journal');
    }

    private function installTheme(string $id): void
    {
        $dir = $this->themesRoot . '/' . $id;
        mkdir($dir, 0777, true);
        file_put_contents(
            $dir . '/theme.json',
            JsonHelper::encode([
                'manifestVersion' => 1,
                'id' => $id,
                'name' => 'Clean Journal',
                'version' => '1.0.0',
            ])
        );
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
