<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Derived catalog queries (It.92). Implementations: JSON (default) or optional SQLite.
 * SSOT remains Markdown/JSON documents — not a second source of truth.
 */
interface QueryIndexInterface
{
    public const DRIVER_JSON = 'json';

    public const DRIVER_SQLITE = 'sqlite';

    /**
     * @return array{entries: list<ContentIndexEntry>, total: int}
     */
    public function query(string $type, PaginationQuery $pagination): array;

    /**
     * @return list<ContentIndexEntry>
     */
    public function search(string $q, ?string $type = null, int $limit = 20, bool $publishedOnly = true): array;

    /**
     * @param array<string, string> $filters
     * @return list<string>
     */
    public function listDistinctTags(string $type, array $filters = []): array;

    /**
     * @param array<string, string> $filters
     * @return list<string>
     */
    public function listDistinctCategories(string $type, array $filters = []): array;

    /**
     * @param array<string, string> $filters
     */
    public function countMatching(string $type, array $filters = []): int;

    /**
     * @param array<string, string> $filters
     * @return list<ContentIndexEntry>
     */
    public function queryEditorialCalendar(
        string $from,
        string $to,
        ?string $type = null,
        array $filters = []
    ): array;

    public function driverId(): string;

    /**
     * Total rows in the active projection (for health / advisor).
     */
    public function entryCount(): int;
}
