<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Performance\PerformanceAggregator;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PHPUnit\Framework\TestCase;

final class PerformanceAggregatorTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = sys_get_temp_dir() . '/apm-agg-' . bin2hex(random_bytes(4)) . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function testSummaryComputesPercentilesAndIoCounters(): void
    {
        $store = $this->makeStore();
        $store->append($this->sample('/api/health', 50.0, 200, 2, 1, 3, 0, 4.0, 1.0));
        $store->append($this->sample('/api/health', 250.0, 500, 0, 2, 1, 1, 8.0, 20.0));
        $store->append($this->sample('/api/pages', 120.0, 200, 1, 0, 4, 0, 2.0, 0.5));

        $summary = (new PerformanceAggregator($store))->summary();

        $this->assertSame(3, $summary['sample_count']);
        $this->assertSame(120.0, $summary['p50_ms']);
        $this->assertSame(250.0, $summary['p95_ms']);
        $this->assertEqualsWithDelta(0.3333, $summary['error_rate'], 0.0001);
        $this->assertSame(3, $summary['cache_hits']);
        $this->assertSame(3, $summary['cache_misses']);
        $this->assertSame(8, $summary['storage_reads']);
        $this->assertSame(1, $summary['storage_writes']);
        $this->assertSame(8.0, $summary['storage_ms_p95']);
        $this->assertSame(20.0, $summary['session_lock_ms_p95']);
        $this->assertNotEmpty($summary['by_route']);
    }

    public function testSummaryReturnsZerosWhenEmpty(): void
    {
        $summary = (new PerformanceAggregator($this->makeStore()))->summary();

        $this->assertSame(0, $summary['sample_count']);
        $this->assertSame(0.0, $summary['error_rate']);
        $this->assertNull($summary['p95_ms']);
        $this->assertNull($summary['storage_ms_p95']);
        $this->assertNull($summary['session_lock_ms_p95']);
        $this->assertNull($summary['apm_lock_wait_ms_max']);
    }

    private function makeStore(): PerformanceSampleStore
    {
        $writer = $this->createMock(FileWriterInterface::class);
        $writer->method('write')->willReturnCallback(function (string $relativePath, string $contents): void {
            file_put_contents($this->path, $contents);
        });

        return new PerformanceSampleStore($writer, $this->path);
    }

    /**
     * @return array{
     *     ts: int,
     *     route: string,
     *     method: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_mb: float,
     *     storage_reads: int,
     *     storage_writes: int,
     *     cache_hits: int,
     *     cache_misses: int
     * }
     */
    private function sample(
        string $route,
        float $durationMs,
        int $status,
        int $cacheHits,
        int $cacheMisses,
        int $storageReads,
        int $storageWrites,
        float $storageMs = 0.0,
        float $sessionLockMs = 0.0
    ): array {
        return [
            'ts' => time(),
            'route' => $route,
            'method' => 'GET',
            'status' => $status,
            'duration_ms' => $durationMs,
            'memory_delta_mb' => 0.1,
            'storage_reads' => $storageReads,
            'storage_writes' => $storageWrites,
            'storage_ms' => $storageMs,
            'session_lock_ms' => $sessionLockMs,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
        ];
    }
}
