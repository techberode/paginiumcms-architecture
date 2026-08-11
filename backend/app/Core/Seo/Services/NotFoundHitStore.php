<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Seo\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use InvalidArgumentException;
use RuntimeException;

/**
 * Aggregated 404 hit counters by day + path (It.80b).
 *
 * @phpstan-type NotFoundRow array{
 *     day: string,
 *     path: string,
 *     hits: int,
 *     lastSeen: string,
 *     refererHosts: array<string, int>
 * }
 */
final class NotFoundHitStore
{
    private const RETENTION_DAYS = 90;

    private const DEFAULT_LIMIT = 50;

    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/metrics/404_hits.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    public function record(string $path, ?string $referer, ?string $userAgent = null): void
    {
        try {
            $path = $this->normalizePath($path);
        } catch (InvalidArgumentException) {
            return;
        }

        $day = gmdate('Y-m-d');
        $refererHost = $this->refererHost($referer);

        $this->withLockedStore(function (array $store) use ($day, $path, $refererHost): array {
            /** @var array<string, NotFoundRow> $rows */
            $rows = $this->rowsFromStore($store);
            $key = $day . '|' . $path;

            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'day' => $day,
                    'path' => $path,
                    'hits' => 0,
                    'lastSeen' => gmdate('c'),
                    'refererHosts' => [],
                ];
            }

            $rows[$key]['hits']++;
            $rows[$key]['lastSeen'] = gmdate('c');

            if ($refererHost !== null) {
                $rows[$key]['refererHosts'][$refererHost] = ($rows[$key]['refererHosts'][$refererHost] ?? 0) + 1;
            }

            $rows = $this->pruneOld($rows);
            $store['schemaVersion'] = 1;
            $store['rows'] = array_values($rows);

            return $store;
        });
    }

    /**
     * @return list<array{path: string, hits: int, lastSeen: string, topReferer: string|null}>
     */
    public function topPaths(int $days, int $limit = self::DEFAULT_LIMIT): array
    {
        $days = max(1, min($days, 90));
        $cutoff = gmdate('Y-m-d', strtotime('-' . $days . ' days UTC'));

        /** @var array<string, array{path: string, hits: int, lastSeen: string, refererHosts: array<string, int>}> $aggregated */
        $aggregated = [];

        foreach ($this->loadRows() as $row) {
            if ($row['day'] < $cutoff) {
                continue;
            }

            $path = $row['path'];
            if (!isset($aggregated[$path])) {
                $aggregated[$path] = [
                    'path' => $path,
                    'hits' => 0,
                    'lastSeen' => $row['lastSeen'],
                    'refererHosts' => [],
                ];
            }

            $aggregated[$path]['hits'] += $row['hits'];
            if ($row['lastSeen'] > $aggregated[$path]['lastSeen']) {
                $aggregated[$path]['lastSeen'] = $row['lastSeen'];
            }

            foreach ($row['refererHosts'] as $host => $count) {
                $aggregated[$path]['refererHosts'][$host] = ($aggregated[$path]['refererHosts'][$host] ?? 0) + $count;
            }
        }

        $rows = array_values($aggregated);
        usort($rows, static fn (array $a, array $b): int => $b['hits'] <=> $a['hits']);
        $rows = array_slice($rows, 0, max(1, min($limit, 200)));

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'path' => $row['path'],
                'hits' => $row['hits'],
                'lastSeen' => $row['lastSeen'],
                'topReferer' => $this->topRefererHost($row['refererHosts']),
            ];
        }

        return $result;
    }

    public function exportCsv(int $days): string
    {
        $lines = ['path,hits,last_seen,top_referer'];
        foreach ($this->topPaths($days, 500) as $row) {
            $lines[] = implode(',', [
                $this->csvCell($row['path']),
                (string) $row['hits'],
                $this->csvCell($row['lastSeen']),
                $this->csvCell($row['topReferer'] ?? ''),
            ]);
        }

        return implode("\n", $lines) . "\n";
    }

    public function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return '/';
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        if (str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '://')) {
            throw new InvalidArgumentException('Invalid path');
        }

        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if (strlen($path) > 512) {
            $path = substr($path, 0, 512);
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function refererHost(?string $referer): ?string
    {
        if ($referer === null || trim($referer) === '') {
            return null;
        }

        $referer = LogSanitizer::value(trim($referer), 2048);
        $host = parse_url($referer, PHP_URL_HOST);

        return is_string($host) && $host !== ''
            ? LogSanitizer::value($host, 255)
            : null;
    }

    /**
     * @param array<string, int> $hosts
     */
    private function topRefererHost(array $hosts): ?string
    {
        if ($hosts === []) {
            return null;
        }

        arsort($hosts);

        foreach ($hosts as $host => $_count) {
            return $host;
        }
    }

    /**
     * @return list<NotFoundRow>
     */
    private function loadRows(): array
    {
        if (!is_file($this->absolutePath)) {
            return [];
        }

        $raw = file_get_contents($this->absolutePath);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values($this->rowsFromStore($decoded));
    }

    /**
     * @param array<string, mixed> $store
     * @return array<string, NotFoundRow>
     */
    private function rowsFromStore(array $store): array
    {
        $rows = [];
        $items = $store['rows'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!isset($item['day'], $item['path'], $item['hits'], $item['lastSeen'])
                || !is_string($item['day'])
                || !is_string($item['path'])
                || !is_string($item['lastSeen'])
            ) {
                continue;
            }

            try {
                $path = $this->normalizePath($item['path']);
            } catch (InvalidArgumentException) {
                continue;
            }

            $refererHosts = [];
            if (isset($item['refererHosts']) && is_array($item['refererHosts'])) {
                foreach ($item['refererHosts'] as $host => $count) {
                    if (is_string($host) && is_numeric($count)) {
                        $refererHosts[LogSanitizer::value($host, 255)] = (int) $count;
                    }
                }
            }

            $key = $item['day'] . '|' . $path;
            $rows[$key] = [
                'day' => $item['day'],
                'path' => $path,
                'hits' => max(0, (int) $item['hits']),
                'lastSeen' => $item['lastSeen'],
                'refererHosts' => $refererHosts,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, NotFoundRow> $rows
     * @return array<string, NotFoundRow>
     */
    private function pruneOld(array $rows): array
    {
        $cutoff = gmdate('Y-m-d', strtotime('-' . self::RETENTION_DAYS . ' days UTC'));
        $pruned = [];

        foreach ($rows as $key => $row) {
            if ($row['day'] >= $cutoff) {
                $pruned[$key] = $row;
            }
        }

        return $pruned;
    }

    private function csvCell(string $value): string
    {
        $sanitized = LogSanitizer::value($value, 2048);

        if (str_contains($sanitized, ',') || str_contains($sanitized, '"') || str_contains($sanitized, "\n")) {
            return '"' . str_replace('"', '""', $sanitized) . '"';
        }

        return $sanitized;
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    private function withLockedStore(callable $callback): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create 404 metrics directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open 404 metrics store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock 404 metrics store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: [])
                : ['schemaVersion' => 1, 'rows' => []];

            if (!is_array($store)) {
                $store = ['schemaVersion' => 1, 'rows' => []];
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
