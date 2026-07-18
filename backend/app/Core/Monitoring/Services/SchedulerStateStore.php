<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Monitoring\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Flat-file state for scheduled monitoring jobs (Iteration 7).
 */
final class SchedulerStateStore
{
    private const REGISTRY = 'data/scheduler-state.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    public function getLastReportAt(): ?string
    {
        $state = $this->load();

        $value = $state['lastReportAt'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setLastReportAt(string $isoTimestamp): void
    {
        $state = $this->load();
        $state['lastReportAt'] = $isoTimestamp;
        $this->save($state);
    }

    public function getLastLogScanAt(): ?string
    {
        $state = $this->load();
        $value = $state['lastLogScanAt'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setLastLogScanAt(string $isoTimestamp): void
    {
        $state = $this->load();
        $state['lastLogScanAt'] = $isoTimestamp;
        $this->save($state);
    }

    /**
     * @return list<string>
     */
    public function getNotifiedLogIds(): array
    {
        $state = $this->load();
        $ids = $state['notifiedLogIds'] ?? [];

        return is_array($ids) ? array_values(array_filter($ids, static fn ($id): bool => is_string($id) && $id !== '')) : [];
    }

    /**
     * @param list<string> $ids
     */
    public function addNotifiedLogIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $state = $this->load();
        $existing = $this->getNotifiedLogIds();
        $merged = array_values(array_unique([...$existing, ...$ids]));
        if (count($merged) > 500) {
            $merged = array_slice($merged, -500);
        }
        $state['notifiedLogIds'] = $merged;
        $this->save($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->load();
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read(self::REGISTRY));

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $state
     */
    private function save(array $state): void
    {
        $this->writer->write(
            self::REGISTRY,
            JsonHelper::encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            true
        );
    }
}
