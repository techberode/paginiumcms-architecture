<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Conflict\Services;

use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Conflict\Models\ConflictRecord;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use RuntimeException;

/**
 * === Služba: ConflictLogger ===
 * Flat-file log konfliktov obsahu (`data/conflicts.json`). Iterácia 3.
 *
 * Súbežnosť: rovnaký princíp ako LockManager – celý cyklus "načítaj → uprav → zapíš"
 * beží pod exkluzívnym `flock(LOCK_EX)`, takže súbežné 409 sa navzájom neprepíšu.
 * Log je ohraničený (`maxRecords`) – drží sa len najnovších N záznamov.
 */
final class ConflictLogger implements ConflictLoggerInterface
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $logFile = 'data/conflicts.json',
        private int $maxRecords = 200
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->logFile, '/');
    }

    public function record(ConflictRecord $record): void
    {
        $this->withLockedLog(function (array &$records) use ($record): void {
            // Najnovší na začiatok, orežeme na maxRecords.
            array_unshift($records, $record);
            if (count($records) > $this->maxRecords) {
                $records = array_slice($records, 0, $this->maxRecords);
            }
        });
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getRecent(int $limit = 100): array
    {
        return $this->withLockedLog(
            static function (array &$records) use ($limit): array {
                return array_slice(array_values($records), 0, max(0, $limit));
            }
        );
    }

    public function clear(): void
    {
        $this->withLockedLog(static function (array &$records): void {
            $records = [];
        });
    }

    // === Blok: Interná atomická práca s logom ===

    /**
     * @template T
     * @param callable(array<int, ConflictRecord>): T $callback
     * @return T
     */
    private function withLockedLog(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť log konfliktov: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať exkluzívny zámok logu konfliktov.');
            }

            $records = $this->readRecords($handle);
            $before = $this->serialize($records);
            $result = $callback($records);
            $after = $this->serialize($records);

            if ($after !== $before) {
                $this->writeRecords($handle, $records);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return array<int, ConflictRecord>
 */private function readRecords($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $records = [];
        foreach ($decoded as $entry) {
            if (is_array($entry)) {
                $records[] = ConflictRecord::fromArray($entry);
            }
        }

        return $records;
    }

    /**
     * @param resource $handle
     * @param array<int, ConflictRecord> $records
 */private function writeRecords($handle, array $records): void
    {
        $payload = json_encode(
            array_map(static fn (ConflictRecord $r): array => $r->jsonSerialize(), array_values($records)),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($payload === false) {
            $payload = '[]';
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);
    }

    /**
     * @param array<int, ConflictRecord> $records
 */private function serialize(array $records): string
    {
        return (string) json_encode(
            array_map(static fn (ConflictRecord $r): array => $r->jsonSerialize(), $records)
        );
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->logFile);
        if ($dir !== '' && $dir !== '.') {
            $this->writer->createDirectory($dir);
        }
    }
}
