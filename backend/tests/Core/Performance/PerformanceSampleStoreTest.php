<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PHPUnit\Framework\TestCase;

final class PerformanceSampleStoreTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = sys_get_temp_dir() . '/apm-samples-' . bin2hex(random_bytes(4)) . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function testRingBufferTrimsToMaxSamples(): void
    {
        $store = $this->makeStore();

        for ($i = 1; $i <= PerformanceSampleStore::MAX_SAMPLES + 5; ++$i) {
            $store->append($this->sample($i));
        }

        $all = $store->all();

        $this->assertCount(PerformanceSampleStore::MAX_SAMPLES, $all);
        $this->assertEquals(6.0, $all[0]['duration_ms']);
        $this->assertEquals((float) (PerformanceSampleStore::MAX_SAMPLES + 5), $all[array_key_last($all)]['duration_ms']);
    }

    public function testClearRemovesSamples(): void
    {
        $store = $this->makeStore();
        $store->append($this->sample(1));
        $store->clear();

        $this->assertSame([], $store->all());
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
    private function sample(int $durationMs): array
    {
        return [
            'ts' => time(),
            'route' => '/api/health',
            'method' => 'GET',
            'status' => 200,
            'duration_ms' => (float) $durationMs,
            'memory_delta_mb' => 0.1,
            'storage_reads' => 0,
            'storage_writes' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
    }
}
