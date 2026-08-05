<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PaginiumCMS\Core\Cache\Services\CacheCapabilityProbe;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Performance\PerformanceBreachStore;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\SafeRemediationService;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SafeRemediationServiceTest extends TestCase
{
    public function testSuggestModeDoesNotApplyAutomaticActions(): void
    {
        $cacheDir = $this->makeCacheDir();
        $service = $this->makeService(
            $this->settingsWithMode(PerformanceGuardSettings::REMEDIATION_SUGGEST),
            $cacheDir
        );

        $result = $service->maybeApplyAutomatic('/api/pages', 'warning', ['purge cache']);

        $this->assertFalse($result['applied']);
        $this->assertSame(PerformanceGuardSettings::REMEDIATION_SUGGEST, $result['mode']);
        $this->removeCacheDir($cacheDir);
    }

    public function testAutomaticSkipsWhenCacheCapabilityFails(): void
    {
        $cacheDir = sys_get_temp_dir() . '/apm-ro-' . uniqid('', true);
        mkdir($cacheDir, 0755, true);
        chmod($cacheDir, 0555);

        try {
            $service = $this->makeService(
                $this->settingsWithMode(PerformanceGuardSettings::REMEDIATION_AUTOMATIC),
                $cacheDir
            );

            $result = $service->maybeApplyAutomatic('/api/pages', 'critical', []);

            $this->assertFalse($result['applied']);
            $this->assertSame('cache_capability_failed', $result['detail'] ?? null);
        } finally {
            chmod($cacheDir, 0755);
            @rmdir($cacheDir);
        }
    }

    private function settingsWithMode(string $mode): PerformanceGuardSettings
    {
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->with('engine')->willReturn([
            'performanceGuardRemediationMode' => $mode,
        ]);

        return new PerformanceGuardSettings($repo);
    }

    private function makeService(PerformanceGuardSettings $settings, string $cachePath): SafeRemediationService
    {
        $driver = new FileDriver($cachePath);
        $cache = new CacheManager($driver);
        $cacheAdmin = new CacheAdminService($cache, new ContentCacheService($cache), $cachePath);
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $settingsRepo->method('group')->with('engine')->willReturn([]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $writer = $this->createMock(FileWriterInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        return new SafeRemediationService(
            $settings,
            $cacheAdmin,
            new CacheCapabilityProbe(),
            new CacheDriverFactory($cachePath),
            $settingsRepo,
            new SecurityLogger($logger),
            new PerformanceBreachStore($reader, $writer)
        );
    }

    private function makeCacheDir(): string
    {
        $dir = sys_get_temp_dir() . '/apm-rem-' . uniqid('', true);
        mkdir($dir, 0755, true);

        return $dir;
    }

    private function removeCacheDir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($dir);
    }
}
