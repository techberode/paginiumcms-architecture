<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Fail-open to JSON driver when SQLite projection errors (It.92c).
 */
final class FallbackQueryIndex implements QueryIndexInterface
{
    public function __construct(
        private QueryIndexInterface $primary,
        private QueryIndexInterface $fallback,
        private ?QueryIndexFailureHandler $failureHandler = null
    ) {
    }

    public function query(string $type, PaginationQuery $pagination): array
    {
        return $this->delegate(fn (QueryIndexInterface $index): array => $index->query($type, $pagination));
    }

    public function search(string $q, ?string $type = null, int $limit = 20, bool $publishedOnly = true): array
    {
        return $this->delegate(
            fn (QueryIndexInterface $index): array => $index->search($q, $type, $limit, $publishedOnly)
        );
    }

    public function listDistinctTags(string $type, array $filters = []): array
    {
        return $this->delegate(fn (QueryIndexInterface $index): array => $index->listDistinctTags($type, $filters));
    }

    public function listDistinctCategories(string $type, array $filters = []): array
    {
        return $this->delegate(
            fn (QueryIndexInterface $index): array => $index->listDistinctCategories($type, $filters)
        );
    }

    public function countMatching(string $type, array $filters = []): int
    {
        return $this->delegate(fn (QueryIndexInterface $index): int => $index->countMatching($type, $filters));
    }

    public function queryEditorialCalendar(
        string $from,
        string $to,
        ?string $type = null,
        array $filters = []
    ): array {
        return $this->delegate(
            fn (QueryIndexInterface $index): array => $index->queryEditorialCalendar($from, $to, $type, $filters)
        );
    }

    public function driverId(): string
    {
        return $this->primary->driverId();
    }

    public function entryCount(): int
    {
        return $this->delegate(fn (QueryIndexInterface $index): int => $index->entryCount());
    }

    /**
     * @template T
     * @param callable(QueryIndexInterface): T $callback
     * @return T
     */
    private function delegate(callable $callback): mixed
    {
        try {
            return $callback($this->primary);
        } catch (\Throwable) {
            $this->failureHandler?->handleQueryFallback();

            return $callback($this->fallback);
        }
    }
}
