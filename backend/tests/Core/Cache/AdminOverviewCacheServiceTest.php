<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Cache;

use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\Cache\AdminOverviewCacheService;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PHPUnit\Framework\TestCase;

final class AdminOverviewCacheServiceTest extends TestCase
{
    private AdminOverviewCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup('storage', null, ['cache' => []]);
        $driver = new FileDriver(vfsStream::url('storage/cache'));
        $cache = new CacheManager($driver, 'test_');
        $this->service = new AdminOverviewCacheService($cache);
    }

    public function testRememberAuditStatsCachesUntilInvalidated(): void
    {
        $calls = 0;
        $loader = static function () use (&$calls): array {
            ++$calls;

            return ['total_events' => $calls];
        };

        $first = $this->service->rememberAuditStats([], $loader);
        $second = $this->service->rememberAuditStats([], $loader);

        $this->assertSame(['total_events' => 1], $first);
        $this->assertSame(['total_events' => 1], $second);

        $this->service->invalidateAuditStats();

        $third = $this->service->rememberAuditStats([], $loader);
        $this->assertSame(['total_events' => 2], $third);
        $this->assertGreaterThan(1, $calls);
    }
}
