<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

class LogWriter implements LogWriterInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private string $storagePath;

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        string $storagePath
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->storagePath = rtrim($storagePath, '/');
    }

    public function write(LogEntry $entry): void
    {
        $date = date('Y-m-d');
        $filename = $date . '.json';
        $path = $this->storagePath . '/' . $filename;

        // Načítanie existujúcich záznamov
        $entries = [];
        try {
            $content = $this->reader->read($path);
            $entries = json_decode($content, true) ?? [];
        } catch (FlatFileException) {
            // Súbor neexistuje - vytvoríme nový
        }

        // Pridanie nového záznamu
        $entries[] = $entry->toArray();

        // Uloženie cez FileWriter
        $this->writer->write(
            $path,
            json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function readAll(): array
    {
        $allEntries = [];
        $files = $this->getLogFiles();

        foreach ($files as $file) {
            try {
                $content = $this->reader->read($this->storagePath . '/' . $file);
                $entries = json_decode($content, true) ?? [];
                $allEntries = array_merge($allEntries, $entries);
            } catch (FlatFileException) {
                continue;
            }
        }

        usort($allEntries, function ($a, $b) {
            $timeA = strtotime($a['timestamp'] ?? '1970-01-01');
            $timeB = strtotime($b['timestamp'] ?? '1970-01-01');
            return $timeB - $timeA;
        });

        return $allEntries;
    }

    public function readLast(int $limit = 100): array
    {
        $allEntries = $this->readAll();
        return array_slice($allEntries, 0, $limit);
    }

    public function readBySeverity(string $severity, int $limit = 100): array
    {
        $allEntries = $this->readAll();
        $filtered = array_filter($allEntries, function ($entry) use ($severity) {
            return ($entry['severity'] ?? '') === $severity;
        });
        return array_slice($filtered, 0, $limit);
    }

    public function readByCategory(string $category, int $limit = 100): array
    {
        $allEntries = $this->readAll();
        $filtered = array_filter($allEntries, function ($entry) use ($category) {
            return ($entry['category'] ?? '') === $category;
        });
        return array_slice($filtered, 0, $limit);
    }

    public function clearOld(int $days = 30): int
    {
        $deleted = 0;
        $cutoff = time() - ($days * 86400);
        $files = $this->getLogFiles();

        foreach ($files as $file) {
            $dateStr = pathinfo($file, PATHINFO_FILENAME);
            $fileTime = strtotime($dateStr);

            if ($fileTime && $fileTime < $cutoff) {
                try {
                    $this->writer->delete($this->storagePath . '/' . $file, false);
                    $deleted++;
                } catch (FlatFileException) {
                    continue;
                }
            }
        }

        return $deleted;
    }

    private function getLogFiles(): array
    {
        $pattern = $this->storagePath . '/*.json';
        $files = glob($pattern);
        if ($files === false) {
            return [];
        }
        return array_map(function ($file) {
            return basename($file);
        }, $files);
    }
}
