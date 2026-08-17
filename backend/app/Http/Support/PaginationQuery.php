<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Normalizované query parametre pre stránkované listy (Iterácia 19).
 */
final class PaginationQuery
{
    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 100;
    public const MIN_SEARCH_LENGTH = 2;

    /**
     * @param array<string, string> $filters
     */
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly string $search,
        public readonly string $sort,
        public readonly array $filters = []
    ) {
    }

    public static function fromRequest(ServerRequestInterface $request, int $defaultPerPage = self::DEFAULT_PER_PAGE): self
    {
        $params = $request->getQueryParams();

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = (int) ($params['per_page'] ?? $params['perPage'] ?? $defaultPerPage);
        $perPage = min(self::MAX_PER_PAGE, max(1, $perPage));

        $search = trim((string) ($params['search'] ?? $params['q'] ?? ''));
        $sort = trim((string) ($params['sort'] ?? '-updatedAt'));

        return new self($page, $perPage, $search, $sort, self::extractFilters($params));
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    public static function extractFilters(array $params): array
    {
        $filters = [];

        if (!empty($params['status'])) {
            $filters['status'] = (string) $params['status'];
        }

        $tag = self::readFilterValue($params, 'tag');
        if ($tag !== '') {
            $filters['tag'] = $tag;
        }

        $category = self::readFilterValue($params, 'category');
        if ($category !== '') {
            $filters['category'] = $category;
        }

        $author = self::readFilterValue($params, 'author');
        if ($author !== '') {
            $filters['author'] = $author;
        }

        $dateFrom = self::readFilterValue($params, 'date_from');
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }

        $dateTo = self::readFilterValue($params, 'date_to');
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }

        $calendarFrom = self::readFilterValue($params, 'calendar_from');
        if ($calendarFrom === '') {
            $calendarFrom = self::readFilterValue($params, 'calendarFrom');
        }
        if ($calendarFrom !== '') {
            $filters['calendar_from'] = $calendarFrom;
        }

        $calendarTo = self::readFilterValue($params, 'calendar_to');
        if ($calendarTo === '') {
            $calendarTo = self::readFilterValue($params, 'calendarTo');
        }
        if ($calendarTo !== '') {
            $filters['calendar_to'] = $calendarTo;
        }

        $stale = self::readFilterValue($params, 'stale');
        if ($stale === '1') {
            $filters['stale'] = '1';
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function readFilterValue(array $params, string $key): string
    {
        $nested = $params['filter'] ?? null;
        if (is_array($nested) && !empty($nested[$key])) {
            return trim((string) $nested[$key]);
        }

        if (!empty($params[$key])) {
            return trim((string) $params[$key]);
        }

        return '';
    }

    public function cacheKeySuffix(): string
    {
        return md5(json_encode([
            'page' => $this->page,
            'perPage' => $this->perPage,
            'search' => $this->search,
            'sort' => $this->sort,
            'filters' => $this->filters,
        ]) ?: '');
    }
}
