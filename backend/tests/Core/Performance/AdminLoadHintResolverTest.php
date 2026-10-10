<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\Performance\AdminLoadHintResolver;
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
        $resolver = new AdminLoadHintResolver(
            $this->settings(false),
            $this->container()->get(PerformanceAggregator::class),
            $this->container()->get(PerformanceBreachStore::class),
        );

        $this->assertSame(
            ['level' => 'normal', 'reasons' => []],
            $resolver->resolve()
        );
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

        $resolver = new AdminLoadHintResolver(
            $this->settings(true, 200),
            $this->container()->get(PerformanceAggregator::class),
            $this->container()->get(PerformanceBreachStore::class),
        );

        $hint = $resolver->resolve();

        $this->assertSame('busy', $hint['level']);
        $this->assertContains('apm_p95', $hint['reasons']);
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
