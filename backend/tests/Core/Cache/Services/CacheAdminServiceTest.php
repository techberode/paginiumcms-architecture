<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Cache\Services;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PHPUnit\Framework\TestCase;

final class CacheAdminServiceTest extends TestCase
{
    private CacheAdminService $service;
    private CacheManager $cache;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/paginium_cache_test_' . uniqid('', true);
        mkdir($this->cacheDir, 0755, true);

        $driver = new FileDriver($this->cacheDir);
        $this->cache = new CacheManager($driver);
        $this->service = new CacheAdminService(
            $this->cache,
            new ContentCacheService($this->cache),
            $this->cacheDir
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->cacheDir);
        parent::tearDown();
    }

    public function testStatsReturnsFileCountAndGenerations(): void
    {
        $this->cache->set('content.pages.list.gen', 2);
        $this->cache->set('demo', 'value');

        $stats = $this->service->stats();

        $this->assertSame($this->cacheDir, $stats['storage_path']);
        $this->assertGreaterThanOrEqual(1, $stats['file_entries']);
        $this->assertSame(2, $stats['generations']['pages']);
    }

    public function testPurgeContentBumpsGenerations(): void
    {
        $this->cache->set('content.pages.list.gen', 1);
        $before = $this->service->stats()['generations']['pages'];

        $result = $this->service->purge(CacheAdminService::SCOPE_CONTENT);

        $this->assertSame('content', $result['scope']);
        $this->assertGreaterThan($before, $this->service->stats()['generations']['pages']);
    }

    public function testPurgeAllClearsCacheFiles(): void
    {
        $this->cache->set('rate_limit:test', 5);
        $this->assertGreaterThan(0, $this->service->stats()['file_entries']);

        $result = $this->service->purge(CacheAdminService::SCOPE_ALL);

        $this->assertSame('all', $result['scope']);
        $this->assertSame(0, $this->service->stats()['file_entries']);
    }

    public function testPurgeRejectsInvalidScope(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->purge('invalid');
    }
}
