<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Deduplicated breach windows for Performance Guard (Iteration 71).
 */
final class PerformanceBreachStore
{
    private const REGISTRY = 'data/metrics/apm-breaches.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOpen(string $route, string $severity): ?array
    {
        foreach ($this->all() as $breach) {
            if (($breach['route'] ?? '') === $route && ($breach['severity'] ?? '') === $severity) {
                if (($breach['resolved_at'] ?? null) === null) {
                    return $breach;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $breach
     */
    public function save(array $breach): void
    {
        $all = $this->all();
        $id = (string) ($breach['id'] ?? '');
        $updated = false;
        foreach ($all as $index => $row) {
            if (($row['id'] ?? '') === $id) {
                $all[$index] = $breach;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $all[] = $breach;
        }

        if (count($all) > 200) {
            $all = array_slice($all, -200);
        }

        $this->persist($all);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 20): array
    {
        $all = $this->all();
        usort($all, static fn (array $a, array $b): int => strcmp((string) ($b['opened_at'] ?? ''), (string) ($a['opened_at'] ?? '')));

        return array_slice($all, 0, max(1, $limit));
    }

    public function clear(): void
    {
        $this->writer->write(self::REGISTRY, '[]', true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function all(): array
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read(self::REGISTRY));
        } catch (\Throwable) {
            return [];
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function persist(array $rows): void
    {
        $this->writer->write(
            self::REGISTRY,
            JsonHelper::encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            true
        );
    }
}
