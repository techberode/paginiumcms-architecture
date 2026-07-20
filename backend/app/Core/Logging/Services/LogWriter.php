<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use JsonException;
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Zápis logov mimo content FileValidator — priamy filesystem I/O.
 */
class LogWriter implements LogWriterInterface
{
    private string $storagePath;

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        string $storagePath
    ) {
        unset($reader, $writer);

        $resolved = realpath($storagePath);
        $this->storagePath = $resolved !== false ? $resolved : rtrim($storagePath, '/');
    }

    public function write(LogEntry $entry): void
    {
        $path = $this->logFilePath(date('Y-m-d') . '.json');
        $this->ensureStorageDirectory($path);

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť log súbor: ' . $path);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok log súboru: ' . $path);
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $entries = $this->decodeLogPayload(is_string($raw) ? $raw : '', $path);
            $entries[] = $entry->toArray();

            ftruncate($handle, 0);
            rewind($handle);
            fwrite(
                $handle,
                JsonHelper::encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    public function readAll(): array
    {
        $allEntries = [];

        foreach ($this->getLogFiles() as $file) {
            $entries = $this->readLogFile($this->logFilePath($file));
            if ($entries !== []) {
                $allEntries = array_merge($allEntries, $entries);
            }
        }

        usort($allEntries, static function ($a, $b) {
            $timeA = strtotime($a['timestamp'] ?? '1970-01-01');
            $timeB = strtotime($b['timestamp'] ?? '1970-01-01');

            return $timeB <=> $timeA;
        });

        return $allEntries;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function readLast(int $limit = 100): array
    {
        return array_slice($this->readAll(), 0, $limit);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function readBySeverity(string $severity, int $limit = 100): array
    {
        $filtered = array_filter(
            $this->readAll(),
            static fn (array $entry): bool => ($entry['severity'] ?? '') === $severity
        );

        return array_slice(array_values($filtered), 0, $limit);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function readByCategory(string $category, int $limit = 100): array
    {
        $filtered = array_filter(
            $this->readAll(),
            static fn (array $entry): bool => ($entry['category'] ?? '') === $category
        );

        return array_slice(array_values($filtered), 0, $limit);
    }

    public function clearOld(int $days = 30): int
    {
        $deleted = 0;
        $cutoff = time() - ($days * 86400);

        foreach ($this->getLogFiles() as $file) {
            $dateStr = pathinfo($file, PATHINFO_FILENAME);
            $fileTime = strtotime($dateStr);

            if ($fileTime !== false && $fileTime < $cutoff) {
                if (@unlink($this->logFilePath($file))) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readSince(string $since, int $limit = 200): array
    {
        $sinceTs = strtotime($since);
        if ($sinceTs === false) {
            return [];
        }

        $matched = [];
        foreach ($this->readAll() as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $ts = strtotime((string) ($entry['timestamp'] ?? ''));
            if ($ts === false || $ts <= $sinceTs) {
                continue;
            }
            $matched[] = $entry;
            if (count($matched) >= $limit) {
                break;
            }
        }

        return $matched;
    }

    private function logFilePath(string $filename): string
    {
        return $this->storagePath . '/' . ltrim($filename, '/');
    }

    /**
     * @return array<int|string, mixed>
     */
    private function readLogFile(string $absolutePath): array
    {
        if (!is_file($absolutePath)) {
            return [];
        }

        $raw = file_get_contents($absolutePath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        return $this->decodeLogPayload($raw, $absolutePath);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeLogPayload(string $raw, string $absolutePath): array
    {
        try {
            return JsonHelper::decode($raw);
        } catch (JsonException) {
            $salvaged = $this->salvageCorruptLogPayload($raw);
            if ($salvaged !== []) {
                $this->backupCorruptLogFile($absolutePath, $raw);
                $this->writeLogFile($absolutePath, $salvaged);

                return $salvaged;
            }

            $this->backupCorruptLogFile($absolutePath, $raw);

            return [];
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    private function salvageCorruptLogPayload(string $raw): array
    {
        $length = strlen($raw);
        if ($length === 0) {
            return [];
        }

        $low = 0;
        $high = $length;
        $best = [];

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $chunk = substr($raw, 0, $mid);

            try {
                $decoded = JsonHelper::decode($chunk);
                $best = $decoded;
                $low = $mid + 1;
            } catch (JsonException) {
                $high = $mid - 1;
            }
        }

        return $best;
    }

    private function backupCorruptLogFile(string $absolutePath, string $raw): void
    {
        $backupPath = $absolutePath . '.corrupt-' . date('Ymd-His');
        if (@file_put_contents($backupPath, $raw) === false) {
            return;
        }

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    /**
     * @param array<int|string, mixed> $entries
     */
    private function writeLogFile(string $absolutePath, array $entries): void
    {
        $this->ensureStorageDirectory($absolutePath);

        file_put_contents(
            $absolutePath,
            JsonHelper::encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function ensureStorageDirectory(string $absolutePath): void
    {
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * @return list<string>
     */
    private function getLogFiles(): array
    {
        $files = glob($this->storagePath . '/*.json');

        if ($files === false) {
            return [];
        }

        return array_map(static fn (string $file): string => basename($file), $files);
    }
}
