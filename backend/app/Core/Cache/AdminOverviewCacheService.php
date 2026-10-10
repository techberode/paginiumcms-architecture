<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

/**
 * Derived cache for heavy admin overview endpoints (P2 — not SSOT).
 *
 * Stale-while-revalidate: expired entries are served immediately; refresh runs
 * after the HTTP response (shutdown) or via CLI {@see AdminOverviewCacheWarmer}.
 */
final class AdminOverviewCacheService
{
    /** Fresh TTL written to cache driver. */
    public const FRESH_TTL_SECONDS = 120;

    /** Serve stale payload up to this age while revalidating. */
    public const STALE_MAX_AGE_SECONDS = 600;

    private const REFRESH_LOCK_SUFFIX = '.refresh_lock';

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

    /**
     * @return array<int|string, mixed>
     */
    public function refreshAuditStats(callable $loader): array
    {
        return $this->refreshArray('audit_stats', $this->filterFingerprint([]), $loader);
    }

    public function invalidateAuditStats(): void
    {
        $this->bumpGeneration('audit_stats');
    }

    /**
     * @return array<int|string, mixed>
     */
    public function rememberJobsOverview(callable $loader, int $recentRunsLimit = 150): array
    {
        $limit = max(5, min(500, $recentRunsLimit));

        return $this->rememberArray('jobs_overview', 'recent_' . $limit, $loader);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function refreshJobsOverview(callable $loader, int $recentRunsLimit = 150): array
    {
        $limit = max(5, min(500, $recentRunsLimit));

        return $this->refreshArray('jobs_overview', 'recent_' . $limit, $loader);
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

    /**
     * @return array<int|string, mixed>
     */
    public function refreshApmSummary(callable $loader): array
    {
        return $this->refreshArray('metrics_apm', 'summary', $loader);
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

    /**
     * @return array<int|string, mixed>
     */
    public function refreshProjectPlansOverview(callable $loader): array
    {
        return $this->refreshArray('project_plans_overview', 'all', $loader);
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
        $key = $this->dataKey($entity, $fingerprint);
        $envelope = $this->readEnvelope($key);

        if ($envelope !== null) {
            $age = time() - $envelope['stored_at'];
            if ($age <= self::FRESH_TTL_SECONDS) {
                return $envelope['data'];
            }
            if ($age <= self::STALE_MAX_AGE_SECONDS) {
                $this->scheduleBackgroundRefresh($key, $loader);

                return $envelope['data'];
            }
        }

        return $this->computeWithCoalescing($key, $loader);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function refreshArray(string $entity, string $fingerprint, callable $loader): array
    {
        $key = $this->dataKey($entity, $fingerprint);

        return $this->writeEnvelope($key, $this->normalizeLoaderResult($loader));
    }

    /**
     * @return array<int|string, mixed>
     */
    private function computeWithCoalescing(string $key, callable $loader): array
    {
        $lockPath = $this->refreshLockPath($key);
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return $this->writeEnvelope($key, $this->normalizeLoaderResult($loader));
        }

        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                $envelope = $this->pollEnvelope($key);
                if ($envelope !== null) {
                    return $envelope['data'];
                }

                flock($handle, LOCK_EX);
            }

            $cached = $this->readEnvelope($key);
            if ($cached !== null && (time() - $cached['stored_at']) <= self::STALE_MAX_AGE_SECONDS) {
                return $cached['data'];
            }

            return $this->writeEnvelope($key, $this->normalizeLoaderResult($loader));
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function scheduleBackgroundRefresh(string $key, callable $loader): void
    {
        $lockKey = $key . self::REFRESH_LOCK_SUFFIX;
        if ($this->cache->has($lockKey)) {
            return;
        }

        $this->cache->set($lockKey, 1, 90);

        register_shutdown_function(function () use ($key, $loader, $lockKey): void {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }

            try {
                $this->writeEnvelope($key, $this->normalizeLoaderResult($loader));
            } finally {
                $this->cache->delete($lockKey);
            }
        });
    }

    /**
     * @return array{data: array<int|string, mixed>, stored_at: int}|null
     */
    private function pollEnvelope(string $key): ?array
    {
        for ($i = 0; $i < 8; ++$i) {
            usleep(50_000);
            $envelope = $this->readEnvelope($key);
            if ($envelope !== null) {
                return $envelope;
            }
        }

        return null;
    }

    /**
     * @return array{data: array<int|string, mixed>, stored_at: int}|null
     */
    private function readEnvelope(string $key): ?array
    {
        $raw = $this->cache->get($key);
        if (!is_array($raw) || !isset($raw['data'], $raw['stored_at']) || !is_array($raw['data'])) {
            return null;
        }

        return [
            'data' => $raw['data'],
            'stored_at' => (int) $raw['stored_at'],
        ];
    }

    /**
     * @param array<int|string, mixed> $data
     * @return array<int|string, mixed>
     */
    private function writeEnvelope(string $key, array $data): array
    {
        $this->cache->set($key, [
            'data' => $data,
            'stored_at' => time(),
        ], self::STALE_MAX_AGE_SECONDS);

        return $data;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function normalizeLoaderResult(callable $loader): array
    {
        $result = $loader();

        return is_array($result) ? $result : [];
    }

    private function dataKey(string $entity, string $fingerprint): string
    {
        $gen = $this->generation($entity);

        return 'admin.' . $entity . '.' . $gen . '.' . $fingerprint;
    }

    private function refreshLockPath(string $key): string
    {
        $dir = sys_get_temp_dir() . '/paginium_admin_cache_locks';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . '/' . hash('sha256', $key) . '.lock';
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
