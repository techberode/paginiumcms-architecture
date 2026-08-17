<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

/**
 * Metadata pre stránkované API odpovede (Iterácia 19).
 *
 * @phpstan-type PaginationMetaArray array{
 *     page: int,
 *     per_page: int,
 *     total: int,
 *     total_pages: int,
 *     tags?: list<string>,
 *     categories?: list<string>,
 *     total_published?: int
 * }
 */
final class PaginationMeta
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly array $extra = []
    ) {
    }

    public function totalPages(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * @return PaginationMetaArray
     */
    public function toArray(): array
    {
        return array_merge([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'total_pages' => $this->totalPages(),
        ], $this->extra);
    }
}
