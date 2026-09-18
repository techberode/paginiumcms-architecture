<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Http\Support\PaginationQuery;
use PDO;
use RuntimeException;

/**
 * Optional derived SQLite catalog (It.92b). SSOT remains JSON/documents.
 */
final class SqliteQueryIndex implements QueryIndexInterface
{
    private ?PDO $pdo = null;

    public function __construct(
        private QueryIndexPaths $paths,
        private ContentStalenessService $staleness,
        private QueryIndexRebuilder $rebuilder
    ) {
    }

    public function query(string $type, PaginationQuery $pagination): array
    {
        $entries = $this->loadEntriesForType($type);
        $entries = $this->applyQueryFilters($entries, $pagination);

        $total = count($entries);
        $offset = ($pagination->page - 1) * $pagination->perPage;
        $pageEntries = array_slice($entries, $offset, $pagination->perPage);

        return ['entries' => $pageEntries, 'total' => $total];
    }

    public function search(string $q, ?string $type = null, int $limit = 20, bool $publishedOnly = true): array
    {
        if (mb_strlen(trim($q)) < PaginationQuery::MIN_SEARCH_LENGTH) {
            return [];
        }

        $match = $this->ftsMatchExpression(trim($q));
        if ($match === '') {
            return [];
        }

        $sql = 'SELECT e.* FROM entries_fts fts
            INNER JOIN entries e ON e.id = fts.rowid
            WHERE fts MATCH :match';
        $params = ['match' => $match];

        if ($type === 'page' || $type === 'article') {
            $sql .= ' AND e.type = :type';
            $params['type'] = $type;
        }

        $sql .= ' ORDER BY e.updated_at DESC LIMIT :lim';

        $stmt = $this->connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':lim', min(100, max(1, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();
        $entries = array_map(fn (array $row): ContentIndexEntry => $this->rowToEntry($row), $rows);

        if ($publishedOnly) {
            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => $e->matchesStatusFilter('published')
            ));
        }

        return array_slice($entries, 0, min(100, max(1, $limit)));
    }

    public function listDistinctTags(string $type, array $filters = []): array
    {
        $entries = $this->applyIndexFilters($this->loadEntriesForType($type), $filters);
        $tags = [];
        foreach ($entries as $entry) {
            foreach ($entry->tags as $tag) {
                $tags[$tag] = true;
            }
        }
        $unique = array_keys($tags);
        sort($unique, SORT_NATURAL | SORT_FLAG_CASE);

        return $unique;
    }

    public function listDistinctCategories(string $type, array $filters = []): array
    {
        $entries = $this->applyIndexFilters($this->loadEntriesForType($type), $filters);
        $categories = [];
        foreach ($entries as $entry) {
            $slug = trim($entry->category);
            if ($slug !== '') {
                $categories[$slug] = true;
            }
        }
        $unique = array_keys($categories);
        sort($unique, SORT_NATURAL | SORT_FLAG_CASE);

        return $unique;
    }

    public function countMatching(string $type, array $filters = []): int
    {
        $entries = $this->applyIndexFilters($this->loadEntriesForType($type), $filters);

        return count($entries);
    }

    public function queryEditorialCalendar(
        string $from,
        string $to,
        ?string $type = null,
        array $filters = []
    ): array {
        $fromDate = $this->normalizeFilterDate($from);
        $toDate = $this->normalizeFilterDate($to);
        if ($fromDate === null || $toDate === null || $fromDate > $toDate) {
            return [];
        }

        $sql = 'SELECT * FROM entries WHERE calendar_date >= :from AND calendar_date <= :to';
        $params = ['from' => $fromDate, 'to' => $toDate];
        if ($type === 'page' || $type === 'article') {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }
        $sql .= ' ORDER BY calendar_date ASC, title COLLATE NOCASE ASC';

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute($params);
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();
        $entries = array_map(fn (array $row): ContentIndexEntry => $this->rowToEntry($row), $rows);

        return $this->applyIndexFilters($entries, $filters, false);
    }

    public function driverId(): string
    {
        return self::DRIVER_SQLITE;
    }

    public function entryCount(): int
    {
        $stmt = $this->connection()->query('SELECT COUNT(*) FROM entries');
        if ($stmt === false) {
            return 0;
        }

        return (int) $stmt->fetchColumn();
    }

    private function connection(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('pdo_sqlite is not available.');
        }

        $path = $this->paths->absolutePath();
        if (!is_readable($path)) {
            throw new RuntimeException('SQLite query index file is missing.');
        }

        $this->pdo = $this->rebuilder->openConnection($path);

        return $this->pdo;
    }

    /**
     * @return list<ContentIndexEntry>
     */
    private function loadEntriesForType(string $type): array
    {
        $stmt = $this->connection()->prepare('SELECT * FROM entries WHERE type = :type');
        $stmt->execute(['type' => $type]);
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return array_map(fn (array $row): ContentIndexEntry => $this->rowToEntry($row), $rows);
    }

    /**
     * @param list<ContentIndexEntry> $entries
     * @return list<ContentIndexEntry>
     */
    private function applyQueryFilters(array $entries, PaginationQuery $query): array
    {
        if (!empty($query->filters['status'])) {
            $status = $query->filters['status'];
            $locale = isset($query->filters['locale']) ? (string) $query->filters['locale'] : null;
            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => $e->matchesStatusFilter($status, $locale)
            ));
        }

        $entries = $this->applyIndexFilters($entries, $query->filters, false);

        if ($query->search !== '' && mb_strlen($query->search) >= PaginationQuery::MIN_SEARCH_LENGTH) {
            $needle = mb_strtolower($query->search);
            $entries = array_values(array_filter(
                $entries,
                static function (ContentIndexEntry $e) use ($needle): bool {
                    if (str_contains(mb_strtolower($e->title), $needle)) {
                        return true;
                    }
                    if (str_contains(mb_strtolower($e->slug), $needle)) {
                        return true;
                    }
                    if (str_contains(mb_strtolower($e->excerpt), $needle)) {
                        return true;
                    }
                    foreach ($e->tags as $tag) {
                        if (str_contains(mb_strtolower($tag), $needle)) {
                            return true;
                        }
                    }

                    return false;
                }
            ));
        }

        return $this->sortEntries($entries, $query->sort);
    }

    /**
     * @param list<ContentIndexEntry> $entries
     * @param array<string, string> $filters
     * @return list<ContentIndexEntry>
     */
    private function applyIndexFilters(array $entries, array $filters, bool $applyStatus = true): array
    {
        if ($applyStatus && !empty($filters['status'])) {
            $status = $filters['status'];
            $locale = isset($filters['locale']) ? (string) $filters['locale'] : null;
            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => $e->matchesStatusFilter($status, $locale)
            ));
        }

        if (!empty($filters['tag'])) {
            $needle = mb_strtolower($filters['tag']);
            $entries = array_values(array_filter(
                $entries,
                static function (ContentIndexEntry $e) use ($needle): bool {
                    foreach ($e->tags as $tag) {
                        if (mb_strtolower($tag) === $needle) {
                            return true;
                        }
                    }

                    return false;
                }
            ));
        }

        if (!empty($filters['category'])) {
            $needle = mb_strtolower(trim($filters['category']));
            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => mb_strtolower(trim($e->category)) === $needle
            ));
        }

        if (!empty($filters['author'])) {
            $needle = mb_strtolower($filters['author']);
            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => str_contains(mb_strtolower($e->author), $needle)
            ));
        }

        $dateFrom = $this->normalizeFilterDate($filters['date_from'] ?? null);
        $dateTo = $this->normalizeFilterDate($filters['date_to'] ?? null);

        if ($dateFrom !== null || $dateTo !== null) {
            $entries = array_values(array_filter(
                $entries,
                static function (ContentIndexEntry $e) use ($dateFrom, $dateTo): bool {
                    $entryDate = ContentIndexEntry::normalizeIndexedDate($e->createdAt) ?? '';
                    if ($entryDate === '') {
                        return false;
                    }
                    if ($dateFrom !== null && $entryDate < $dateFrom) {
                        return false;
                    }
                    if ($dateTo !== null && $entryDate > $dateTo) {
                        return false;
                    }

                    return true;
                }
            ));
        }

        if (($filters['stale'] ?? '') === '1') {
            if ($this->staleness->thresholdMonths() === 0) {
                return [];
            }

            $entries = array_values(array_filter(
                $entries,
                fn (ContentIndexEntry $e): bool => $this->staleness->entryIsStale($e)
            ));
        }

        return $entries;
    }

    /**
     * @param list<ContentIndexEntry> $entries
     * @return list<ContentIndexEntry>
     */
    private function sortEntries(array $entries, string $sort): array
    {
        $desc = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');

        usort($entries, static function (ContentIndexEntry $a, ContentIndexEntry $b) use ($field, $desc): int {
            $valueA = match ($field) {
                'title' => $a->title,
                'slug' => $a->slug,
                'status' => $a->status,
                'createdAt' => $a->createdAt,
                default => $a->updatedAt,
            };
            $valueB = match ($field) {
                'title' => $b->title,
                'slug' => $b->slug,
                'status' => $b->status,
                'createdAt' => $b->createdAt,
                default => $b->updatedAt,
            };

            $cmp = strcmp((string) $valueA, (string) $valueB);

            return $desc ? -$cmp : $cmp;
        });

        return $entries;
    }

    private function normalizeFilterDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function ftsMatchExpression(string $q): string
    {
        $parts = preg_split('/\s+/u', trim($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $escaped = [];
        foreach ($parts as $part) {
            $part = preg_replace('/[^\p{L}\p{N}._-]/u', '', $part) ?? '';
            if ($part !== '') {
                $escaped[] = '"' . str_replace('"', '', $part) . '"';
            }
        }

        return implode(' AND ', $escaped);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowToEntry(array $row): ContentIndexEntry
    {
        /** @var mixed $tagsJson */
        $tagsJson = json_decode((string) ($row['tags_json'] ?? '[]'), true);
        /** @var mixed $localesJson */
        $localesJson = json_decode((string) ($row['locales_json'] ?? '[]'), true);
        /** @var mixed $localeStatusJson */
        $localeStatusJson = json_decode((string) ($row['locale_status_json'] ?? '{}'), true);

        return ContentIndexEntry::fromArray([
            'slug' => (string) ($row['slug'] ?? ''),
            'type' => (string) ($row['type'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'author' => (string) ($row['author'] ?? ''),
            'path' => (string) ($row['path'] ?? ''),
            'excerpt' => (string) ($row['excerpt'] ?? ''),
            'tags' => is_array($tagsJson) ? $tagsJson : [],
            'category' => (string) ($row['category'] ?? ''),
            'updatedAt' => (string) ($row['updated_at'] ?? ''),
            'createdAt' => (string) ($row['created_at'] ?? ''),
            'scheduledAt' => (string) ($row['scheduled_at'] ?? ''),
            'lastReviewedAt' => (string) ($row['last_reviewed_at'] ?? ''),
            'defaultLocale' => (string) ($row['default_locale'] ?? 'sk'),
            'locales' => is_array($localesJson) ? $localesJson : [],
            'localeStatus' => is_array($localeStatusJson) ? $localeStatusJson : [],
        ]);
    }
}
