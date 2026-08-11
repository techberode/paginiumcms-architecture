<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Services;

use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Hourly comment submission counters per client hash (It.80c).
 */
final class CommentSubmissionVelocityStore
{
    private const RETENTION_HOURS = 48;

    public function __construct(
        private string $storeFile = '',
    ) {
        if ($this->storeFile === '') {
            $this->storeFile = dirname(__DIR__, 4) . '/data/metrics/comment_velocity.json';
        }
    }

    public function record(string $clientHash): void
    {
        $hour = gmdate('Y-m-d-H');
        $this->withLockedStore(function (array $store) use ($hour, $clientHash): array {
            /** @var array<string, array<string, int>> $buckets */
            $buckets = is_array($store['buckets'] ?? null) ? $store['buckets'] : [];
            $buckets[$hour][$clientHash] = ($buckets[$hour][$clientHash] ?? 0) + 1;
            $store['schemaVersion'] = 1;
            $store['buckets'] = $this->prune($buckets);

            return $store;
        });
    }

    public function countRecent(string $clientHash, int $hours): int
    {
        $hours = max(1, min($hours, self::RETENTION_HOURS));
        $raw = $this->loadStore();
        $buckets = is_array($raw['buckets'] ?? null) ? $raw['buckets'] : [];
        $total = 0;

        for ($i = 0; $i < $hours; $i++) {
            $hour = gmdate('Y-m-d-H', strtotime('-' . $i . ' hours UTC'));
            $total += (int) (($buckets[$hour][$clientHash] ?? 0));
        }

        return $total;
    }

    /**
     * @param array<string, array<string, int>> $buckets
     * @return array<string, array<string, int>>
     */
    private function prune(array $buckets): array
    {
        $cutoff = gmdate('Y-m-d-H', strtotime('-' . self::RETENTION_HOURS . ' hours UTC'));
        $pruned = [];

        foreach ($buckets as $hour => $counts) {
            if ($hour >= $cutoff) {
                $pruned[$hour] = $counts;
            }
        }

        return $pruned;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadStore(): array
    {
        if (!is_file($this->storeFile)) {
            return ['schemaVersion' => 1, 'buckets' => []];
        }

        $raw = file_get_contents($this->storeFile);
        if (!is_string($raw) || trim($raw) === '') {
            return ['schemaVersion' => 1, 'buckets' => []];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['schemaVersion' => 1, 'buckets' => []];
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    private function withLockedStore(callable $callback): void
    {
        $dir = dirname($this->storeFile);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create comment velocity directory: ' . $dir);
        }

        $handle = fopen($this->storeFile, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open comment velocity store: ' . $this->storeFile);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock comment velocity store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: ['schemaVersion' => 1, 'buckets' => []])
                : ['schemaVersion' => 1, 'buckets' => []];

            if (!is_array($store)) {
                $store = ['schemaVersion' => 1, 'buckets' => []];
            }

            $store = $callback($store);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, JsonHelper::encode($store, JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
