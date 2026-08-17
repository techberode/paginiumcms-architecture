<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\Content\Models\CategoryRecord;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file taxonomy registry at data/taxonomy/categories.json (It.84a).
 */
final class CategoryRepository
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $registryFile = 'data/taxonomy/categories.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/')
            . '/' . ltrim($this->registryFile, '/');
    }

    /**
     * @return list<array{slug: string, label: string}>
     */
    public function list(): array
    {
        $records = $this->readAll();
        $items = [];
        foreach ($records as $record) {
            $items[] = $record->toArray();
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));

        return $items;
    }

    /**
     * @param list<string> $slugs In-use category slugs from content index.
     * @return list<array{slug: string, label: string}>
     */
    public function summarizeForSlugs(array $slugs): array
    {
        $records = $this->readAll();
        $items = [];
        foreach ($slugs as $rawSlug) {
            $slug = CategoryRecord::normalizeSlug($rawSlug);
            if ($slug === '') {
                continue;
            }
            $record = $records[$slug] ?? new CategoryRecord($slug, $this->humanizeSlug($slug));
            $items[] = $record->toArray();
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));

        return $items;
    }

    public function get(string $slug): ?CategoryRecord
    {
        $slug = CategoryRecord::normalizeSlug($slug);
        if ($slug === '') {
            return null;
        }

        return $this->readAll()[$slug] ?? null;
    }

    public function save(string $slug, string $label): CategoryRecord
    {
        $slug = CategoryRecord::normalizeSlug($slug);
        if ($slug === '') {
            throw new RuntimeException('Invalid category slug.');
        }

        $label = trim($label);
        if ($label === '') {
            throw new RuntimeException('Category label is required.');
        }

        $record = new CategoryRecord($slug, $label);

        $this->withLockedRegistry(function (array &$records) use ($record): void {
            $records[$record->slug] = $record;
        });

        return $record;
    }

    public function delete(string $slug): void
    {
        $slug = CategoryRecord::normalizeSlug($slug);
        if ($slug === '') {
            throw new RuntimeException('Invalid category slug.');
        }

        $this->withLockedRegistry(function (array &$records) use ($slug): void {
            unset($records[$slug]);
        });
    }

    /**
     * @return array<string, CategoryRecord>
     */
    private function readAll(): array
    {
        return $this->withLockedRegistry(static fn (array $records): array => $records);
    }

    /**
     * @template T
     * @param callable(array<string, CategoryRecord>&): T $callback
     * @return T
     */
    private function withLockedRegistry(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open category registry: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock category registry.');
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
     * @return array<string, CategoryRecord>
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
        foreach ($decoded as $slug => $entry) {
            if (!is_string($slug) || !is_array($entry)) {
                continue;
            }
            $records[$slug] = CategoryRecord::fromArray($slug, $entry);
        }

        return $records;
    }

    /**
     * @param resource $handle
     * @param array<string, CategoryRecord> $records
     */
    private function writeRecords($handle, array $records): void
    {
        $payload = [];
        foreach ($records as $slug => $record) {
            $payload[$slug] = $record->toArray();
        }
        ksort($payload);

        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json);
        fflush($handle);
    }

    /**
     * @param array<string, CategoryRecord> $records
     */
    private function serialize(array $records): string
    {
        $payload = [];
        foreach ($records as $slug => $record) {
            $payload[$slug] = $record->toArray();
        }
        ksort($payload);

        return JsonHelper::encode($payload);
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->registryFile);
        if ($dir !== '' && $dir !== '.') {
            $this->writer->createDirectory($dir);
        }
    }

    private function humanizeSlug(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }
}
