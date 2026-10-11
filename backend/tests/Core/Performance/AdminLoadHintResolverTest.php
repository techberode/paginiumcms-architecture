<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\Performance\AdminActiveSessionStore;
use PaginiumCMS\Core\Performance\AdminLoadHintResolver;
use PaginiumCMS\Core\Performance\AdminLoadHintSettings;
use PaginiumCMS\Core\Performance\HostMetricsService;
use PaginiumCMS\Core\Performance\HostMetricsSettings;
use PaginiumCMS\Tests\Support\HostMetricsTestFactory;
use PaginiumCMS\Core\Performance\PerformanceAggregator;
use PaginiumCMS\Core\Performance\PerformanceBreachStore;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class AdminLoadHintResolverTest extends TestCase
{
    public function testReturnsNormalWhenPerformanceGuardDisabled(): void
    {
        $resolver = $this->makeResolver($this->settings(false));

        $hint = $resolver->resolve();
        $this->assertSame('normal', $hint['level']);
        $this->assertSame([], $hint['reasons']);
        $this->assertGreaterThanOrEqual(0, $hint['active_admin_sessions']);
    }

    public function testBusyWhenP95ExceedsWarning(): void
    {
        $samples = $this->container()->get(PerformanceSampleStore::class);
        $samples->clear();
        for ($i = 0; $i < 5; $i++) {
            $samples->append([
                'ts' => time(),
                'route' => 'GET /api/admin/dashboard/overview',
                'method' => 'GET',
                'status' => 200,
                'duration_ms' => 800.0,
                'memory_delta_mb' => 0.1,
                'storage_reads' => 1,
                'storage_writes' => 0,
                'cache_hits' => 0,
                'cache_misses' => 0,
            ]);
        }

        $resolver = $this->makeResolver($this->settings(true, 200));

        $hint = $resolver->resolve();

        $this->assertSame('busy', $hint['level']);
        $this->assertContains('apm_p95', $hint['reasons']);
    }

    public function testBusyWhenHostLoadExceedsThresholdEvenIfApmDisabled(): void
    {
        $settingsRepo = $this->engineSettings(hostMetricsEnabled: true, loadHintThreshold: 4.0);
        $snapshot = [
            'collected_at' => gmdate('c'),
            'load' => ['1' => 6.5, '5' => 4.0, '15' => 2.0],
        ];

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(true);
        $reader->method('read')->willReturn(json_encode($snapshot, JSON_THROW_ON_ERROR));
        $writer = $this->createMock(FileWriterInterface::class);

        $hostMetrics = HostMetricsTestFactory::service($settingsRepo, $reader, $writer);
        $hostSettings = new HostMetricsSettings($settingsRepo);

        $resolver = new AdminLoadHintResolver(
            $this->settings(false),
            $this->container()->get(PerformanceAggregator::class),
            $this->container()->get(PerformanceBreachStore::class),
            $hostMetrics,
            $hostSettings,
            $this->container()->get(AdminActiveSessionStore::class),
            $this->container()->get(AdminLoadHintSettings::class),
        );

        $hint = $resolver->resolve();
        $this->assertSame('busy', $hint['level']);
        $this->assertContains('host_load_1m', $hint['reasons']);
    }

    public function testBusyWhenActiveAdminSessionsExceedThreshold(): void
    {
        $sessions = new AdminActiveSessionStore(
            new CacheManager(new MemoryDriver(), 'load_hint_sess_' . uniqid('', true))
        );
        $sessions->touch('sess-a');
        $sessions->touch('sess-b');
        $sessions->touch('sess-c');
        $sessions->touch('sess-d');

        $repo = $this->engineSettings(activeSessionsThreshold: 4);
        $resolver = new AdminLoadHintResolver(
            $this->settings(false),
            $this->container()->get(PerformanceAggregator::class),
            $this->container()->get(PerformanceBreachStore::class),
            $this->container()->get(HostMetricsService::class),
            new HostMetricsSettings($repo),
            $sessions,
            new AdminLoadHintSettings($repo),
        );

        $hint = $resolver->resolve();
        $this->assertSame('busy', $hint['level']);
        $this->assertContains('admin_active_sessions', $hint['reasons']);
        $this->assertSame(4, $hint['active_admin_sessions']);
    }

    private function makeResolver(PerformanceGuardSettings $guardSettings): AdminLoadHintResolver
    {
        return new AdminLoadHintResolver(
            $guardSettings,
            $this->container()->get(PerformanceAggregator::class),
            $this->container()->get(PerformanceBreachStore::class),
            $this->container()->get(HostMetricsService::class),
            $this->container()->get(HostMetricsSettings::class),
            $this->container()->get(AdminActiveSessionStore::class),
            $this->container()->get(AdminLoadHintSettings::class),
        );
    }

    /**
     * @return SettingsRepositoryInterface&MockObject
     */
    private function engineSettings(
        bool $hostMetricsEnabled = false,
        float $loadHintThreshold = 0.0,
        int $activeSessionsThreshold = 0,
    ): SettingsRepositoryInterface {
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->willReturnCallback(
            static fn (string $group): array => $group === 'engine'
                ? [
                    'hostMetricsEnabled' => $hostMetricsEnabled,
                    'adminLoadHintHostLoad1Min' => $loadHintThreshold,
                    'adminLoadHintActiveSessionsMin' => $activeSessionsThreshold,
                ]
                : []
        );

        return $repo;
    }

    private function settings(bool $enabled, int $warningMs = 200): PerformanceGuardSettings
    {
        /** @var SettingsRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(SettingsRepositoryInterface::class);
        $repo->method('group')->willReturnCallback(static function (string $group) use ($enabled, $warningMs): array {
            if ($group !== 'engine') {
                return [];
            }

            return [
                'performanceGuardEnabled' => $enabled,
                'performanceGuardLatencyMsWarning' => $warningMs,
                'performanceGuardLatencyMsCritical' => max($warningMs, 500),
            ];
        });

        return new PerformanceGuardSettings($repo);
    }
}
