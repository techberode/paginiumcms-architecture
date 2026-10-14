<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Support;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;

/**
 * Shared tag filter matching for content index queries (facet-expanded needles).
 */
final class ArticleTagFilter
{
    public const NEEDLES_FILTER_KEY = '_tag_needles';

    /**
     * @param array<string, string> $filters
     * @return list<string> lowercase tag needles
     */
    public static function needlesFromFilters(array $filters): array
    {
        if (!empty($filters[self::NEEDLES_FILTER_KEY])) {
            $parts = explode("\x1e", $filters[self::NEEDLES_FILTER_KEY]);

            return array_values(array_filter(
                array_map(static fn (string $part): string => mb_strtolower(trim($part)), $parts),
                static fn (string $part): bool => $part !== ''
            ));
        }

        if (!empty($filters['tag'])) {
            return [mb_strtolower(trim($filters['tag']))];
        }

        return [];
    }

    /**
     * @param list<string> $needles lowercase
     */
    public static function entryMatches(ContentIndexEntry $entry, array $needles): bool
    {
        if ($needles === []) {
            return true;
        }

        foreach ($entry->tags as $tag) {
            if (in_array(mb_strtolower($tag), $needles, true)) {
                return true;
            }
        }

        return false;
    }
}
