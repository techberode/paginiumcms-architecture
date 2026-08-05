<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Layout\Models\ShortcodeRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file registry at data/shortcodes/registry.json (It.67a).
 */
final class ShortcodeRegistry
{
    private string $absoluteRegistryPath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $registryFile = 'data/shortcodes/registry.json',
    ) {
        $this->absoluteRegistryPath = rtrim($this->reader->getBasePath(), '/')
            . '/' . ltrim($this->registryFile, '/');
    }

    /**
     * @return array<string, ShortcodeRecord>
     */
    public function all(): array
    {
        return $this->withLockedRegistry(static fn (array $records): array => $records);
    }

    public function get(string $name): ?ShortcodeRecord
    {
        $name = $this->normalizeName($name);

        return $this->withLockedRegistry(function (array $records) use ($name): ?ShortcodeRecord {
            return $records[$name] ?? null;
        });
    }

    public function upsert(ShortcodeRecord $record): void
    {
        $name = $this->normalizeName($record->name);

        $this->withLockedRegistry(function (array &$records) use ($name, $record): void {
            $records[$name] = $record;
        });
    }

    public function remove(string $name): void
    {
        $name = $this->normalizeName($name);

        $this->withLockedRegistry(function (array &$records) use ($name): void {
            unset($records[$name]);
        });
    }

    /**
     * @template T
     * @param callable(array<string, ShortcodeRecord>&): T $callback
     * @return T
     */
    private function withLockedRegistry(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absoluteRegistryPath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open shortcode registry: ' . $this->absoluteRegistryPath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock shortcode registry.');
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
     * @return array<string, ShortcodeRecord>
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
        foreach ($decoded as $name => $entry) {
            if (!is_string($name) || !is_array($entry)) {
                continue;
            }

            $records[$name] = ShortcodeRecord::fromArray($name, $entry);
        }

        return $records;
    }

    /**
     * @param resource $handle
     * @param array<string, ShortcodeRecord> $records
     */
    private function writeRecords($handle, array $records): void
    {
        $payload = [];
        foreach ($records as $name => $record) {
            $payload[$name] = $record->toArray();
        }

        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT);

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json);
        fflush($handle);
    }

    /**
     * @param array<string, ShortcodeRecord> $records
     */
    private function serialize(array $records): string
    {
        $data = [];
        foreach ($records as $name => $record) {
            $data[$name] = $record->toArray();
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

    private function normalizeName(string $name): string
    {
        return trim($name);
    }
}
