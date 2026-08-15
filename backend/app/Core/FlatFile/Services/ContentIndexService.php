<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Http\Support\PaginationQuery;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file index obsahu pre rýchle listy, search a stránkovanie (Iterácia 19).
 *
 * Úložisko: `data/index/content.json` – atomický zápis cez flock(LOCK_EX).
 */
final class ContentIndexService
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private LocalizedContentNormalizer $localizedNormalizer,
        private ContentStalenessService $staleness,
        private string $indexFile = 'data/index/content.json'
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->indexFile, '/');
    }

    public function upsertFromContent(Content $content, string $type): void
    {
        $canonical = $this->localizedNormalizer->normalize($content);
        $entry = ContentIndexEntry::fromContent($content, $type, '', $canonical);
        $path = $content->getPath();

        $this->withLockedIndex(function (array &$items) use ($entry, $path, $type): void {
            $items = array_values(array_filter(
                $items,
                static fn (array $row): bool => !(
                    (($row['slug'] ?? '') === $entry->slug && ($row['type'] ?? '') === $entry->type)
                    || ($path !== '' && ($row['path'] ?? '') === $path && ($row['type'] ?? '') === $type)
                )
            ));
            $items[] = $entry->toArray();
        });
    }

    public function remove(string $type, string $slug): void
    {
        $this->withLockedIndex(function (array &$items) use ($type, $slug): void {
            $items = array_values(array_filter(
                $items,
                static fn (array $row): bool => !(
                    ($row['slug'] ?? '') === $slug && ($row['type'] ?? '') === $type
                )
            ));
        });
    }

    public function removeByPath(string $type, string $path): void
    {
        if ($path === '') {
            return;
        }

        $this->withLockedIndex(function (array &$items) use ($type, $path): void {
            $items = array_values(array_filter(
                $items,
                static fn (array $row): bool => !(
                    ($row['path'] ?? '') === $path && ($row['type'] ?? '') === $type
                )
            ));
        });
    }

    /**
     * @return array{entries: list<ContentIndexEntry>, total: int}
     */
    public function query(string $type, PaginationQuery $query): array
    {
        return $this->withLockedIndex(function (array &$items) use ($type, $query): array {
            $entries = array_map(
                fn (array $row): ContentIndexEntry => ContentIndexEntry::fromArray($row),
                $items
            );

            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => $e->type === $type
            ));

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

            $entries = $this->sortEntries($entries, $query->sort);

            $total = count($entries);
            $offset = ($query->page - 1) * $query->perPage;
            $pageEntries = array_slice($entries, $offset, $query->perPage);

            return ['entries' => $pageEntries, 'total' => $total];
        });
    }

    /**
     * @return list<ContentIndexEntry>
     */
    public function search(string $q, ?string $type = null, int $limit = 20, bool $publishedOnly = true): array
    {
        if (mb_strlen(trim($q)) < PaginationQuery::MIN_SEARCH_LENGTH) {
            return [];
        }

        $filters = $publishedOnly ? ['status' => 'published'] : [];
        $query = new PaginationQuery(1, min(100, max(1, $limit)), trim($q), '-updatedAt', $filters);

        if ($type === 'page' || $type === 'article') {
            $result = $this->query($type, $query);

            return $result['entries'];
        }

        $pages = $this->query('page', $query);
        $articles = $this->query('article', $query);

        $merged = array_merge($pages['entries'], $articles['entries']);
        usort($merged, static fn (ContentIndexEntry $a, ContentIndexEntry $b): int => strcmp($b->updatedAt, $a->updatedAt));

        return array_slice($merged, 0, min(100, max(1, $limit)));
    }

    /**
     * @param array<string, string> $filters
     * @return list<string>
     */
    public function listDistinctTags(string $type, array $filters = []): array
    {
        return $this->withLockedIndex(function (array &$items) use ($type, $filters): array {
            $entries = array_map(
                fn (array $row): ContentIndexEntry => ContentIndexEntry::fromArray($row),
                $items
            );

            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => $e->type === $type
            ));

            $entries = $this->applyIndexFilters($entries, $filters);

            $tags = [];
            foreach ($entries as $entry) {
                foreach ($entry->tags as $tag) {
                    $tags[$tag] = true;
                }
            }

            $unique = array_keys($tags);
            sort($unique, SORT_NATURAL | SORT_FLAG_CASE);

            return $unique;
        });
    }

    /**
     * @param array<string, string> $filters
     */
    public function countMatching(string $type, array $filters = []): int
    {
        return $this->withLockedIndex(function (array &$items) use ($type, $filters): int {
            $entries = array_map(
                fn (array $row): ContentIndexEntry => ContentIndexEntry::fromArray($row),
                $items
            );

            $entries = array_values(array_filter(
                $entries,
                static fn (ContentIndexEntry $e): bool => $e->type === $type
            ));

            $entries = $this->applyIndexFilters($entries, $filters);

            return count($entries);
        });
    }

    /**
     * Editorial calendar slice — entries whose derived calendar date falls in [from, to] (It.81d).
     *
     * @param array<string, string> $filters
     * @return list<ContentIndexEntry>
     */
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

        return $this->withLockedIndex(function (array &$items) use ($fromDate, $toDate, $type, $filters): array {
            $entries = array_values(array_map(
                fn (array $row): ContentIndexEntry => ContentIndexEntry::fromArray($row),
                $items
            ));

            if ($type === 'page' || $type === 'article') {
                $entries = array_values(array_filter(
                    $entries,
                    static fn (ContentIndexEntry $e): bool => $e->type === $type
                ));
            }

            $entries = $this->applyIndexFilters($entries, $filters, false);

            $entries = array_values(array_filter(
                $entries,
                static function (ContentIndexEntry $e) use ($fromDate, $toDate): bool {
                    $calendarDate = $e->calendarDate();
                    if ($calendarDate === '') {
                        return false;
                    }

                    return $calendarDate >= $fromDate && $calendarDate <= $toDate;
                }
            ));

            usort($entries, static function (ContentIndexEntry $a, ContentIndexEntry $b): int {
                $dateCmp = strcmp($a->calendarDate(), $b->calendarDate());
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }

                return strcasecmp($a->title, $b->title);
            });

            return $entries;
        });
    }

    public function rebuild(ContentRepositoryInterface $repository): void
    {
        $items = [];

        foreach ($repository->findAllPages() as $page) {
            $canonical = $this->localizedNormalizer->normalize($page);
            $items[] = ContentIndexEntry::fromContent($page, 'page', '', $canonical)->toArray();
        }

        foreach ($repository->findAllArticles() as $article) {
            $canonical = $this->localizedNormalizer->normalize($article);
            $items[] = ContentIndexEntry::fromContent($article, 'article', '', $canonical)->toArray();
        }

        $this->withLockedIndex(function (array &$stored) use ($items): void {
            $stored = $items;
        });
    }

    public function ensureBuilt(ContentRepositoryInterface $repository): void
    {
        if (!$this->reader->exists($this->indexFile)) {
            $this->rebuild($repository);

            return;
        }

        $items = $this->readIndexItemsFromDisk();
        if ($items !== []) {
            return;
        }

        if ($repository->count('page') + $repository->count('article') > 0) {
            $this->rebuild($repository);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readIndexItemsFromDisk(): array
    {
        if (!is_readable($this->absolutePath)) {
            return [];
        }

        $raw = file_get_contents($this->absolutePath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = $decoded['items'] ?? [];

        return is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
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

    /**
     * @template T
     * @param callable(array<int, array<string, mixed>>): T $callback
     * @return T
     */
    private function withLockedIndex(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť content index: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok content indexu.');
            }

            $items = $this->readItems($handle);
            $before = JsonHelper::encode($items);
            $result = $callback($items);
            $after = JsonHelper::encode($items);

            if ($after !== $before) {
                $this->writeItems($handle, $items);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->absolutePath)) {
            file_put_contents($this->absolutePath, JsonHelper::encode(['version' => 1, 'items' => []], JSON_PRETTY_PRINT));
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readItems(mixed $handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = $decoded['items'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        /** @var list<array<string, mixed>> $normalized */
        $normalized = array_values(array_filter($items, 'is_array'));

        return $normalized;
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function writeItems(mixed $handle, array $items): void
    {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, JsonHelper::encode(['version' => 1, 'items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
    }
}
