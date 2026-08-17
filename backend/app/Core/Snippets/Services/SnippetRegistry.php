<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Snippets\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Snippets\Models\SnippetRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file registry at data/snippets/registry.json (It.81f).
 */
final class SnippetRegistry
{
    private string $absoluteRegistryPath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $registryFile = 'data/snippets/registry.json',
    ) {
        $this->absoluteRegistryPath = rtrim($this->reader->getBasePath(), '/')
            . '/' . ltrim($this->registryFile, '/');
    }

    /**
     * @return array<string, SnippetRecord>
     */
    public function all(): array
    {
        return $this->withLockedRegistry(static fn (array $records): array => $records);
    }

    public function get(string $name): ?SnippetRecord
    {
        $name = $this->normalizeName($name);

        return $this->withLockedRegistry(function (array $records) use ($name): ?SnippetRecord {
            return $records[$name] ?? null;
        });
    }

    public function upsert(SnippetRecord $record): void
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
     * @param callable(array<string, SnippetRecord>&): T $callback
     * @return T
     */
    private function withLockedRegistry(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absoluteRegistryPath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open snippet registry: ' . $this->absoluteRegistryPath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock snippet registry.');
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
     * @return array<string, SnippetRecord>
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

            $records[$name] = SnippetRecord::fromArray($name, $entry);
        }

        return $records;
    }

    /**
     * @param resource $handle
     * @param array<string, SnippetRecord> $records
     */
    private function writeRecords($handle, array $records): void
    {
        $payload = [];
        foreach ($records as $name => $record) {
            $payload[$name] = $record->toArray();
        }

        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json);
        fflush($handle);
    }

    /**
     * @param array<string, SnippetRecord> $records
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
