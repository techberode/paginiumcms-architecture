<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Aggregates flat-file logs from app, audit, event and user stores.
 */
final class ApplicationLogReader
{
    /** @var array<string, string> */
    private array $sources;

    /**
     * @param array<string, string> $sources Map source id => absolute directory path
     */
    public function __construct(array $sources)
    {
        $this->sources = $sources;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function query(
        ?string $severity = null,
        ?string $source = null,
        ?string $category = null,
        ?string $search = null,
        int $limit = 100,
        int $offset = 0,
        string $archivedFilter = 'active'
    ): array {
        $entries = $this->filterEntries($severity, $source, $category, $search, $archivedFilter);

        return array_slice($entries, $offset, max(1, $limit));
    }

    public function count(
        ?string $severity = null,
        ?string $source = null,
        ?string $category = null,
        ?string $search = null,
        string $archivedFilter = 'active'
    ): int {
        return count($this->filterEntries($severity, $source, $category, $search, $archivedFilter));
    }

    /**
     * @param list<string> $ids
     */
    public function deleteByIds(array $ids): BulkBatchResult
    {
        $idSet = $this->normalizeIdSet($ids);
        $batch = new BulkBatchResult();

        if ($idSet === []) {
            return $batch;
        }

        $found = [];

        $this->mutateAllFiles(function (array $entries) use (&$found, $idSet): array {
            $next = [];

            foreach ($entries as $entry) {
                $id = (string) ($entry['id'] ?? '');
                if ($id !== '' && isset($idSet[$id])) {
                    $found[$id] = true;
                    continue;
                }

                $next[] = $entry;
            }

            return $next;
        });

        foreach (array_keys($idSet) as $id) {
            if (isset($found[$id])) {
                $batch->addSuccess($id);
            } else {
                $batch->addFailure($id, 'Log neexistuje');
            }
        }

        return $batch;
    }

    /**
     * @param list<string> $ids
     */
    public function archiveByIds(array $ids): BulkBatchResult
    {
        $idSet = $this->normalizeIdSet($ids);
        $batch = new BulkBatchResult();

        if ($idSet === []) {
            return $batch;
        }

        $found = [];

        $this->mutateAllFiles(function (array $entries) use (&$found, $idSet): array {
            $next = [];

            foreach ($entries as $entry) {
                $id = (string) ($entry['id'] ?? '');
                if ($id !== '' && isset($idSet[$id])) {
                    $entry['archived'] = true;
                    $entry['archivedAt'] = date('c');
                    $found[$id] = true;
                }

                $next[] = $entry;
            }

            return $next;
        });

        foreach (array_keys($idSet) as $id) {
            if (isset($found[$id])) {
                $batch->addSuccess($id);
            } else {
                $batch->addFailure($id, 'Log neexistuje');
            }
        }

        return $batch;
    }

    /**
     * @return array{deleted_files: int, deleted_entries: int}
     */
    public function deleteAll(): array
    {
        $deletedFiles = 0;
        $deletedEntries = 0;

        foreach ($this->sources as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = glob(rtrim($directory, '/') . '/*.json');
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $raw = file_get_contents($file);
                if ($raw !== false && trim($raw) !== '') {
                    try {
                        $decoded = JsonHelper::decode($raw);
                        $deletedEntries += count(array_filter(
                            $decoded,
                            static fn (mixed $item): bool => is_array($item)
                        ));
                    } catch (\JsonException) {
                        // Count as one corrupt blob if unreadable.
                        ++$deletedEntries;
                    }
                }

                if (@unlink($file)) {
                    ++$deletedFiles;
                }
            }
        }

        return [
            'deleted_files' => $deletedFiles,
            'deleted_entries' => $deletedEntries,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function severityStats(int $hours = 24): array
    {
        $cutoff = time() - ($hours * 3600);
        $stats = [
            LogSeverity::DEBUG => 0,
            LogSeverity::INFO => 0,
            LogSeverity::WARNING => 0,
            LogSeverity::ERROR => 0,
            LogSeverity::CRITICAL => 0,
        ];

        foreach ($this->filterEntries(null, null, null, null, 'active') as $entry) {
            $ts = strtotime((string) ($entry['timestamp'] ?? ''));
            if ($ts === false || $ts < $cutoff) {
                continue;
            }

            $severity = strtoupper((string) ($entry['severity'] ?? ''));
            if (isset($stats[$severity])) {
                ++$stats[$severity];
            }
        }

        return [
            'debug' => $stats[LogSeverity::DEBUG],
            'info' => $stats[LogSeverity::INFO],
            'warning' => $stats[LogSeverity::WARNING],
            'error' => $stats[LogSeverity::ERROR],
            'critical' => $stats[LogSeverity::CRITICAL],
        ];
    }

    /**
     * @return list<string>
     */
    public function availableSources(): array
    {
        return array_keys($this->sources);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function filterEntries(
        ?string $severity,
        ?string $source,
        ?string $category,
        ?string $search,
        string $archivedFilter
    ): array {
        $entries = $this->loadAll($source);

        if ($archivedFilter === 'active') {
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => ($entry['archived'] ?? false) !== true
            ));
        } elseif ($archivedFilter === 'archived') {
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => ($entry['archived'] ?? false) === true
            ));
        }

        if ($severity !== null && $severity !== '') {
            $severityUpper = strtoupper($severity);
            if (LogSeverity::isValid($severityUpper)) {
                $entries = array_values(array_filter(
                    $entries,
                    static fn (array $entry): bool => strtoupper((string) ($entry['severity'] ?? '')) === $severityUpper
                ));
            }
        }

        if ($category !== null && $category !== '') {
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => ($entry['category'] ?? '') === $category
            ));
        }

        if ($search !== null && trim($search) !== '') {
            $needle = mb_strtolower(trim($search));
            $entries = array_values(array_filter(
                $entries,
                static function (array $entry) use ($needle): bool {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) ($entry['message'] ?? ''),
                        (string) ($entry['category'] ?? ''),
                        (string) ($entry['ip'] ?? ''),
                        (string) ($entry['userId'] ?? ''),
                        (string) ($entry['timestamp'] ?? ''),
                        json_encode($entry['context'] ?? [], JSON_UNESCAPED_UNICODE) ?: '',
                    ]));

                    return str_contains($haystack, $needle);
                }
            ));
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadAll(?string $sourceFilter): array
    {
        $all = [];

        foreach ($this->sources as $sourceId => $directory) {
            if ($sourceFilter !== null && $sourceFilter !== '' && $sourceId !== $sourceFilter) {
                continue;
            }

            foreach ($this->readDirectory($directory) as $entry) {
                $entry['source'] = $sourceId;
                $all[] = $entry;
            }
        }

        usort($all, static function (array $a, array $b): int {
            $timeA = strtotime((string) ($a['timestamp'] ?? '1970-01-01'));
            $timeB = strtotime((string) ($b['timestamp'] ?? '1970-01-01'));

            return ($timeB ?: 0) <=> ($timeA ?: 0);
        });

        return $all;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $entries = [];
        $files = glob(rtrim($directory, '/') . '/*.json');
        if ($files === false) {
            return [];
        }

        rsort($files);

        foreach ($files as $file) {
            $raw = file_get_contents($file);
            if ($raw === false || trim($raw) === '') {
                continue;
            }

            try {
                $decoded = JsonHelper::decode($raw);
            } catch (\JsonException) {
                continue;
            }

            foreach ($decoded as $entry) {
                if (is_array($entry)) {
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * @param callable(array<int, array<string, mixed>>): array<int, array<string, mixed>> $mutator
     */
    private function mutateAllFiles(callable $mutator): void
    {
        foreach ($this->sources as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = glob(rtrim($directory, '/') . '/*.json');
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $this->mutateFile($file, $mutator);
            }
        }
    }

    /**
     * @param callable(array<int, array<string, mixed>>): array<int, array<string, mixed>> $mutator
     */
    private function mutateFile(string $file, callable $mutator): void
    {
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť log súbor: ' . $file);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok log súboru: ' . $file);
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $entries = [];

            if ($raw !== false && trim($raw) !== '') {
                try {
                    $decoded = JsonHelper::decode($raw);
                    $entries = array_values(array_filter(
                        $decoded,
                        static fn (mixed $item): bool => is_array($item)
                    ));
                } catch (\JsonException) {
                    $entries = [];
                }
            }

            $next = $mutator($entries);
            $payload = JsonHelper::encode(array_values($next), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $payload);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param list<string> $ids
     * @return array<string, true>
     */
    private function normalizeIdSet(array $ids): array
    {
        $set = [];

        foreach ($ids as $id) {
            $normalized = trim((string) $id);
            if ($normalized !== '') {
                $set[$normalized] = true;
            }
        }

        return $set;
    }
}
