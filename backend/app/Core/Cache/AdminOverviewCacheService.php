<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

/**
 * Derived cache for heavy admin overview endpoints (P2 — not SSOT).
 *
 * Uses generation counters so invalidation is O(1); TTL bounds staleness when
 * invalidation is skipped (jobs/APM under cron load).
 */
final class AdminOverviewCacheService
{
    public const TTL_SECONDS = 120;

    public function __construct(private CacheManager $cache)
    {
    }

    /**
     * @param array<int|string, mixed> $filters
     * @return array<int|string, mixed>
     */
    public function rememberAuditStats(array $filters, callable $loader): array
    {
        return $this->rememberArray('audit_stats', $this->filterFingerprint($filters), $loader);
    }

    public function invalidateAuditStats(): void
    {
        $this->bumpGeneration('audit_stats');
    }

    /**
     * @return array<int|string, mixed>
     */
    public function rememberJobsOverview(callable $loader): array
    {
        return $this->rememberArray('jobs_overview', 'all', $loader);
    }

    public function invalidateJobsOverview(): void
    {
        $this->bumpGeneration('jobs_overview');
    }

    /**
     * @return array<int|string, mixed>
     */
    public function rememberApmSummary(callable $loader): array
    {
        return $this->rememberArray('metrics_apm', 'summary', $loader);
    }

    public function invalidateApmSummary(): void
    {
        $this->bumpGeneration('metrics_apm');
    }

    /**
     * @return array<int|string, mixed>
     */
    public function rememberProjectPlansOverview(callable $loader): array
    {
        return $this->rememberArray('project_plans_overview', 'all', $loader);
    }

    public function invalidateProjectPlansOverview(): void
    {
        $this->bumpGeneration('project_plans_overview');
    }

    /**
     * @return array<int|string, mixed>
     */
    private function rememberArray(string $entity, string $fingerprint, callable $loader): array
    {
        $gen = $this->generation($entity);
        $key = 'admin.' . $entity . '.' . $gen . '.' . $fingerprint;

        $value = $this->cache->rememberLocked($key, static function () use ($loader): array {
            $result = $loader();

            return is_array($result) ? $result : [];
        }, self::TTL_SECONDS);

        return is_array($value) ? $value : [];
    }

    private function generation(string $entity): int
    {
        return (int) $this->cache->get('admin.' . $entity . '.gen', 0);
    }

    private function bumpGeneration(string $entity): void
    {
        $this->cache->increment('admin.' . $entity . '.gen', 1);
    }

    /**
     * @param array<int|string, mixed> $filters
     */
    private function filterFingerprint(array $filters): string
    {
        if ($filters === []) {
            return 'default';
        }

        ksort($filters);

        return md5(json_encode($filters, JSON_THROW_ON_ERROR) ?: '');
    }
}
