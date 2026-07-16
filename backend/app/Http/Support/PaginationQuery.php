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

        $filters = [];
        if (!empty($params['status'])) {
            $filters['status'] = (string) $params['status'];
        }

        return new self($page, $perPage, $search, $sort, $filters);
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
