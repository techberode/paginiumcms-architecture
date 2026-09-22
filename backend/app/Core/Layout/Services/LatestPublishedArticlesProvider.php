<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Published article headlines for shortcodes — index-only (no ContentRepository) to avoid DI cycles.
 */
final class LatestPublishedArticlesProvider
{
    public function __construct(
        private ContentIndexService $contentIndex,
    ) {
    }

    /**
     * @return list<array{slug: string, title: string}>
     */
    public function list(int $limit): array
    {
        $limit = max(1, min(20, $limit));
        $query = new PaginationQuery(1, $limit, '', '-updatedAt', ['status' => 'published']);
        $result = $this->contentIndex->query('article', $query);

        $out = [];
        foreach ($result['entries'] as $entry) {
            $out[] = [
                'slug' => $entry->slug,
                'title' => $entry->title !== '' ? $entry->title : $entry->slug,
            ];
        }

        return $out;
    }
}
