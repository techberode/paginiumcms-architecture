<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Cache;

use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\Cache\AdminOverviewCacheService;
use PaginiumCMS\Core\Cache\AdminOverviewCacheSettings;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AdminOverviewCacheServiceTest extends TestCase
{
    private AdminOverviewCacheService $service;

    private CacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup('storage', null, ['cache' => []]);
        $driver = new FileDriver(vfsStream::url('storage/cache'));
        $this->cache = new CacheManager($driver, 'test_');
        $this->service = new AdminOverviewCacheService($this->cache, $this->cacheSettings(false));
    }

    public function testCronOnlyMissDoesNotInvokeLoader(): void
    {
        $service = new AdminOverviewCacheService($this->cache, $this->cacheSettings(true));

        $calls = 0;
        $result = $service->rememberAuditStats([], static function () use (&$calls): array {
            ++$calls;

            return ['total_events' => 99];
        });

        $this->assertSame(0, $calls);
        $this->assertSame(0, $result['total_events']);
        $this->assertSame('miss', $result['_cache']['status'] ?? null);
    }

    private function cacheSettings(bool $cronOnly): AdminOverviewCacheSettings
    {
        /** @var SettingsRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->willReturnCallback(
            static fn (string $group): array => $group === 'engine'
                ? ['adminP2CacheCronOnly' => $cronOnly]
                : []
        );

        return new AdminOverviewCacheSettings($repo);
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

    public function testServesStalePayloadWithoutBlockingLoader(): void
    {
        $this->service->refreshAuditStats(static fn (): array => ['total_events' => 42]);

        $this->cache->set('admin.audit_stats.0.default', [
            'data' => ['total_events' => 42],
            'stored_at' => time() - (AdminOverviewCacheService::FRESH_TTL_SECONDS + 10),
        ], AdminOverviewCacheService::STALE_MAX_AGE_SECONDS);

        $calls = 0;
        $result = $this->service->rememberAuditStats([], static function () use (&$calls): array {
            ++$calls;

            return ['total_events' => 99];
        });

        $this->assertSame(['total_events' => 42], $result);
        $this->assertSame(0, $calls);
    }

    public function testServesExpiredSnapshotWithoutSyncLoader(): void
    {
        $this->cache->set('admin.audit_stats.0.default', [
            'data' => ['total_events' => 7],
            'stored_at' => time() - (AdminOverviewCacheService::STALE_MAX_AGE_SECONDS + 120),
        ], AdminOverviewCacheService::STALE_MAX_AGE_SECONDS);

        $calls = 0;
        $result = $this->service->rememberAuditStats([], static function () use (&$calls): array {
            ++$calls;

            return ['total_events' => 99];
        });

        $this->assertSame(['total_events' => 7], $result);
        $this->assertSame(0, $calls);
    }
}
