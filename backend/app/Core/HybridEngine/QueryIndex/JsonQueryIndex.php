<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Default query index: delegates to {@see ContentIndexService} / content.json (It.92a).
 */
final class JsonQueryIndex implements QueryIndexInterface
{
    public function __construct(
        private ContentIndexService $contentIndex
    ) {
    }

    public function query(string $type, PaginationQuery $pagination): array
    {
        return $this->contentIndex->query($type, $pagination);
    }

    public function search(string $q, ?string $type = null, int $limit = 20, bool $publishedOnly = true): array
    {
        return $this->contentIndex->search($q, $type, $limit, $publishedOnly);
    }

    public function listDistinctTags(string $type, array $filters = []): array
    {
        return $this->contentIndex->listDistinctTags($type, $filters);
    }

    public function listDistinctCategories(string $type, array $filters = []): array
    {
        return $this->contentIndex->listDistinctCategories($type, $filters);
    }

    public function countMatching(string $type, array $filters = []): int
    {
        return $this->contentIndex->countMatching($type, $filters);
    }

    public function queryEditorialCalendar(
        string $from,
        string $to,
        ?string $type = null,
        array $filters = []
    ): array {
        return $this->contentIndex->queryEditorialCalendar($from, $to, $type, $filters);
    }

    public function driverId(): string
    {
        return self::DRIVER_JSON;
    }

    public function entryCount(): int
    {
        return $this->contentIndex->countAllEntries();
    }
}
