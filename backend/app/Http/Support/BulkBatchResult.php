<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

/**
 * Aggregates per-item outcomes for admin bulk operations.
 */
final class BulkBatchResult
{
    /** @var list<array{id: string, ok: bool, error?: string}> */
    private array $results = [];

    public function addSuccess(string $id): void
    {
        $this->results[] = ['id' => $id, 'ok' => true];
    }

    public function addFailure(string $id, string $error): void
    {
        $this->results[] = ['id' => $id, 'ok' => false, 'error' => $error];
    }

    public function processed(): int
    {
        return count($this->results);
    }

    public function succeeded(): int
    {
        return count(array_filter($this->results, static fn (array $row): bool => $row['ok']));
    }

    public function failed(): int
    {
        return $this->processed() - $this->succeeded();
    }

    /**
     * @return array{
     *     processed: int,
     *     succeeded: int,
     *     failed: int,
     *     results: list<array{id: string, ok: bool, error?: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'processed' => $this->processed(),
            'succeeded' => $this->succeeded(),
            'failed' => $this->failed(),
            'results' => $this->results,
        ];
    }
}
