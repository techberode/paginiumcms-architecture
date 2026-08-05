<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * Percentile and error-rate aggregation for APM samples (Iteration 71).
 */
final class PerformanceAggregator
{
    public function __construct(
        private PerformanceSampleStore $samples
    ) {
    }

    /**
     * @return array{
     *     sample_count: int,
     *     error_rate: float,
     *     p50_ms: float|null,
     *     p95_ms: float|null,
     *     p99_ms: float|null,
     *     cache_hits: int,
     *     cache_misses: int,
     *     storage_reads: int,
     *     storage_writes: int,
     *     by_route: list<array<string, mixed>>
     * }
     */
    public function summary(int $topRoutes = 10): array
    {
        $rows = $this->samples->all();
        $durations = [];
        $errors = 0;
        $cacheHits = 0;
        $cacheMisses = 0;
        $storageReads = 0;
        $storageWrites = 0;
        $routeBuckets = [];

        foreach ($rows as $row) {
            $duration = (float) ($row['duration_ms'] ?? 0);
            $durations[] = $duration;
            $status = (int) ($row['status'] ?? 200);
            if ($status >= 500) {
                ++$errors;
            }

            $cacheHits += (int) ($row['cache_hits'] ?? 0);
            $cacheMisses += (int) ($row['cache_misses'] ?? 0);
            $storageReads += (int) ($row['storage_reads'] ?? 0);
            $storageWrites += (int) ($row['storage_writes'] ?? 0);

            $route = (string) ($row['route'] ?? 'unknown');
            if (!isset($routeBuckets[$route])) {
                $routeBuckets[$route] = ['route' => $route, 'count' => 0, 'durations' => []];
            }
            ++$routeBuckets[$route]['count'];
            $routeBuckets[$route]['durations'][] = $duration;
        }

        $count = count($rows);
        sort($durations);

        $byRoute = [];
        foreach ($routeBuckets as $bucket) {
            $routeDurations = $bucket['durations'];
            sort($routeDurations);
            $byRoute[] = [
                'route' => $bucket['route'],
                'count' => $bucket['count'],
                'p95_ms' => $this->percentile($routeDurations, 95),
            ];
        }

        usort($byRoute, static fn (array $a, array $b): int => ($b['p95_ms'] ?? 0) <=> ($a['p95_ms'] ?? 0));

        return [
            'sample_count' => $count,
            'error_rate' => $count > 0 ? round($errors / $count, 4) : 0.0,
            'p50_ms' => $this->percentile($durations, 50),
            'p95_ms' => $this->percentile($durations, 95),
            'p99_ms' => $this->percentile($durations, 99),
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'storage_reads' => $storageReads,
            'storage_writes' => $storageWrites,
            'by_route' => array_slice($byRoute, 0, max(1, $topRoutes)),
        ];
    }

    /**
     * @param list<float> $values
     */
    private function percentile(array $values, int $percentile): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $index = (int) ceil(($percentile / 100) * count($values)) - 1;
        $index = max(0, min(count($values) - 1, $index));

        return round($values[$index], 2);
    }
}
