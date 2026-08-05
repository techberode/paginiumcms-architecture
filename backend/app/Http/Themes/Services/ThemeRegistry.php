<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Http\Themes\Models\ThemeRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file registry at data/themes.json (It.67b).
 */
final class ThemeRegistry
{
    private string $absoluteRegistryPath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $registryFile = 'data/themes.json',
    ) {
        $this->absoluteRegistryPath = rtrim($this->reader->getBasePath(), '/')
            . '/' . ltrim($this->registryFile, '/');
    }

    /**
     * @return array<string, ThemeRecord>
     */
    public function all(): array
    {
        return $this->withLockedRegistry(static fn (array $records): array => $records);
    }

    public function get(string $id): ?ThemeRecord
    {
        $id = $this->normalizeId($id);

        return $this->withLockedRegistry(function (array $records) use ($id): ?ThemeRecord {
            return $records[$id] ?? null;
        });
    }

    public function upsert(ThemeRecord $record): void
    {
        $id = $this->normalizeId($record->id);

        $this->withLockedRegistry(function (array &$records) use ($id, $record): void {
            $records[$id] = $record;
        });
    }

    public function remove(string $id): void
    {
        $id = $this->normalizeId($id);

        $this->withLockedRegistry(function (array &$records) use ($id): void {
            unset($records[$id]);
        });
    }

    /**
     * @template T
     * @param callable(array<string, ThemeRecord>&): T $callback
     * @return T
     */
    private function withLockedRegistry(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absoluteRegistryPath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open theme registry: ' . $this->absoluteRegistryPath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock theme registry.');
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
     * @return array<string, ThemeRecord>
     */
    private function readRecords($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($raw);
        } catch (\JsonException) {
            return [];
        }

        $records = [];
        foreach ($decoded as $id => $entry) {
            if (!is_string($id) || !is_array($entry)) {
                continue;
            }

            $records[$id] = ThemeRecord::fromArray($id, $entry);
        }

        return $records;
    }

    /**
     * @param resource $handle
     * @param array<string, ThemeRecord> $records
     */
    private function writeRecords($handle, array $records): void
    {
        $payload = [];
        foreach ($records as $id => $record) {
            $payload[$id] = $record->toArray();
        }

        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT);

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json);
        fflush($handle);
    }

    /**
     * @param array<string, ThemeRecord> $records
     */
    private function serialize(array $records): string
    {
        $data = [];
        foreach ($records as $id => $record) {
            $data[$id] = $record->toArray();
        }
        ksort($data);

        return JsonHelper::encode($data);
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->registryFile);
        if ($dir !== '' && $dir !== '.') {
            $this->writer->createDirectory($dir);
        }
    }

    private function normalizeId(string $id): string
    {
        return trim($id);
    }
}
