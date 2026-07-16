<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

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
        $entries = $this->readLogFile($path);
        $entries[] = $entry->toArray();
        $this->writeLogFile($path, $entries);
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

        $decoded = JsonHelper::decode((string) file_get_contents($absolutePath));

        return $decoded;
    }

    /**
     * @param array<int|string, mixed> $entries
     */
    private function writeLogFile(string $absolutePath, array $entries): void
    {
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $absolutePath,
            JsonHelper::encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
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
