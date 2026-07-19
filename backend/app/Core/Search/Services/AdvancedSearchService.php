<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Search\Services;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Search\AdminRouteCatalog;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Unified search — public (published content) vs admin (content + media + routes).
 */
final class AdvancedSearchService
{
    private const DEFAULT_LIMIT_PER_TYPE = 8;
    private const MAX_LIMIT_PER_TYPE = 20;

    public function __construct(
        private ContentIndexService $index,
        private MediaRepositoryInterface $media
    ) {
    }

    /**
     * @param list<string> $types
     * @return list<array<string, mixed>>
     */
    public function searchPublic(string $query, array $types, int $limitPerType): array
    {
        $types = $this->normalizeTypes($types, publicScope: true);
        $limitPerType = $this->normalizeLimit($limitPerType);
        $results = [];

        if (in_array('page', $types, true) || in_array('article', $types, true)) {
            $contentTypes = array_values(array_intersect($types, ['page', 'article']));
            foreach ($this->searchContent($query, $contentTypes, $limitPerType, publishedOnly: true) as $row) {
                $results[] = $row;
            }
        }

        return $this->sortResults($results);
    }

    /**
     * @param list<string> $types
     * @return array{query: string, scope: string, results: list<array<string, mixed>>, counts: array<string, int>}
     */
    public function searchAdmin(string $query, array $types, int $limitPerType, User $user): array
    {
        $types = $this->normalizeTypes($types, publicScope: false);
        $limitPerType = $this->normalizeLimit($limitPerType);
        $results = [];
        $counts = [
            'page' => 0,
            'article' => 0,
            'media' => 0,
            'route' => 0,
        ];

        $contentTypes = array_values(array_intersect($types, ['page', 'article']));
        if ($contentTypes !== []) {
            foreach ($this->searchContent($query, $contentTypes, $limitPerType, publishedOnly: false) as $row) {
                $type = (string) ($row['type'] ?? '');
                if ($type === 'page' || $type === 'article') {
                    ++$counts[$type];
                }
                $results[] = $row;
            }
        }

        if (in_array('media', $types, true)) {
            foreach ($this->searchMedia($query, $limitPerType) as $row) {
                ++$counts['media'];
                $results[] = $row;
            }
        }

        if (in_array('route', $types, true)) {
            foreach ($this->searchRoutes($query, $limitPerType, $user) as $row) {
                ++$counts['route'];
                $results[] = $row;
            }
        }

        return [
            'query' => $query,
            'scope' => 'admin',
            'results' => $this->sortResults($results),
            'counts' => $counts,
        ];
    }

    /**
     * @param list<string> $contentTypes
     * @return list<array<string, mixed>>
     */
    private function searchContent(string $query, array $contentTypes, int $limitPerType, bool $publishedOnly): array
    {
        if ($contentTypes === []) {
            return [];
        }

        $results = [];
        $typeCounts = [];

        foreach ($contentTypes as $contentType) {
            $entries = $this->index->search($query, $contentType, $limitPerType, $publishedOnly);
            foreach ($entries as $entry) {
                $typeCounts[$contentType] = ($typeCounts[$contentType] ?? 0) + 1;
                if ($typeCounts[$contentType] > $limitPerType) {
                    continue;
                }

                $row = $entry->toSearchResult();
                $row['adminPath'] = $entry->type === 'article'
                    ? '/articles/' . $entry->slug
                    : '/pages/' . $entry->slug;
                $row['subtitle'] = $entry->excerpt !== '' ? $entry->excerpt : $entry->status;
                $results[] = $row;
            }
        }

        return $results;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchMedia(string $query, int $limit): array
    {
        $needle = mb_strtolower(trim($query));
        if ($needle === '') {
            return [];
        }

        $results = [];
        foreach ($this->media->findAll() as $file) {
            $haystack = mb_strtolower(
                $file->getFileName() . ' '
                . $file->getTitle() . ' '
                . $file->getAltText() . ' '
                . $file->getPath()
            );
            if (!str_contains($haystack, $needle)) {
                continue;
            }

            $results[] = [
                'type' => 'media',
                'title' => $file->getTitle() !== '' ? $file->getTitle() : $file->getFileName(),
                'subtitle' => $file->getPath(),
                'path' => $file->getPath(),
                'adminPath' => '/media',
                'mimeType' => $file->getMimeType(),
                'updatedAt' => $file->getUploadedAtDateTime()->format('c'),
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchRoutes(string $query, int $limit, User $user): array
    {
        $needle = mb_strtolower(trim($query));
        if ($needle === '') {
            return [];
        }

        $isAdmin = $user->hasRole('ADMIN') || $user->hasRole('SUPER_ADMIN');
        $results = [];

        foreach (AdminRouteCatalog::routes() as $route) {
            if ($route['adminOnly'] && !$isAdmin) {
                continue;
            }

            $haystack = mb_strtolower($route['title'] . ' ' . $route['keywords'] . ' ' . $route['path']);
            if (!str_contains($haystack, $needle)) {
                continue;
            }

            $results[] = [
                'type' => 'route',
                'title' => $route['title'],
                'subtitle' => 'Admin modul',
                'path' => $route['path'],
                'adminPath' => $route['path'],
                'routeId' => $route['id'],
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @param list<string> $types
     * @return list<string>
     */
    private function normalizeTypes(array $types, bool $publicScope): array
    {
        $allowed = $publicScope
            ? ['page', 'article']
            : ['page', 'article', 'media', 'route'];

        if ($types === []) {
            return $allowed;
        }

        $normalized = [];
        foreach ($types as $type) {
            $type = strtolower(trim($type));
            if ($type !== '' && in_array($type, $allowed, true)) {
                $normalized[] = $type;
            }
        }

        return $normalized !== [] ? array_values(array_unique($normalized)) : $allowed;
    }

    private function normalizeLimit(int $limitPerType): int
    {
        if ($limitPerType < 1) {
            return self::DEFAULT_LIMIT_PER_TYPE;
        }

        return min(self::MAX_LIMIT_PER_TYPE, $limitPerType);
    }

    /**
     * @param list<array<string, mixed>> $results
     * @return list<array<string, mixed>>
     */
    private function sortResults(array $results): array
    {
        usort(
            $results,
            static function (array $a, array $b): int {
                $typeOrder = ['route' => 0, 'page' => 1, 'article' => 2, 'media' => 3];
                $ta = $typeOrder[$a['type'] ?? ''] ?? 9;
                $tb = $typeOrder[$b['type'] ?? ''] ?? 9;
                if ($ta !== $tb) {
                    return $ta <=> $tb;
                }

                return strcmp((string) ($b['updatedAt'] ?? ''), (string) ($a['updatedAt'] ?? ''));
            }
        );

        return $results;
    }
}
