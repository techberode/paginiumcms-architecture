<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Persists the latest host metrics snapshot (It.82d).
 */
final class HostMetricsStore
{
    public const REGISTRY = 'data/metrics/host-latest.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public function save(array $snapshot): void
    {
        $this->writer->write(self::REGISTRY, JsonHelper::encode($snapshot), true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latest(): ?array
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return null;
        }

        $decoded = JsonHelper::decode($this->reader->read(self::REGISTRY));
        if (!isset($decoded['collected_at'])) {
            return null;
        }

        /** @var array<string, mixed> $snapshot */
        $snapshot = $decoded;

        return $snapshot;
    }

    public function clear(): void
    {
        $this->writer->write(self::REGISTRY, '{}', true);
    }
}
