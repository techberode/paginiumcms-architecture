<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Support\JsonHelper;

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
        int $offset = 0
    ): array {
        $entries = $this->loadAll($source);

        if ($severity !== null && $severity !== '' && LogSeverity::isValid($severity)) {
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => ($entry['severity'] ?? '') === $severity
            ));
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

        return array_slice($entries, $offset, max(1, $limit));
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

        foreach ($this->loadAll(null) as $entry) {
            $ts = strtotime((string) ($entry['timestamp'] ?? ''));
            if ($ts === false || $ts < $cutoff) {
                continue;
            }

            $severity = (string) ($entry['severity'] ?? '');
            if (isset($stats[$severity])) {
                ++$stats[$severity];
            }
        }

        return $stats;
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

            $decoded = JsonHelper::decode($raw);

            foreach ($decoded as $entry) {
                if (is_array($entry)) {
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }
}
