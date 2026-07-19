<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Services;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\ContentCacheService;
use InvalidArgumentException;

/**
 * Admin operácie nad cache (štatistiky + manuálne vymazanie).
 */
final class CacheAdminService
{
    public const SCOPE_CONTENT = 'content';
    public const SCOPE_ALL = 'all';

    public function __construct(
        private CacheManager $cache,
        private ContentCacheService $contentCache,
        private string $cacheDirectory
    ) {
    }

    /**
     * @return array{
     *     storage_path: string,
     *     file_entries: int,
     *     generations: array<string, int>
     * }
     */
    public function stats(): array
    {
        return [
            'storage_path' => $this->cacheDirectory,
            'file_entries' => $this->countCacheFiles(),
            'generations' => [
                'pages' => (int) $this->cache->get('content.pages.list.gen', 0),
                'articles' => (int) $this->cache->get('content.articles.list.gen', 0),
                'feeds' => (int) $this->cache->get('content.feeds.gen', 0),
            ],
        ];
    }

    /**
     * @return array{scope: string, file_entries_before: int, file_entries_after: int}
     */
    public function purge(string $scope): array
    {
        if (!in_array($scope, [self::SCOPE_CONTENT, self::SCOPE_ALL], true)) {
            throw new InvalidArgumentException('Neplatný rozsah cache: ' . $scope);
        }

        $before = $this->countCacheFiles();

        $this->contentCache->purgeAll();

        if ($scope === self::SCOPE_ALL) {
            $this->cache->clear();
        }

        return [
            'scope' => $scope,
            'file_entries_before' => $before,
            'file_entries_after' => $this->countCacheFiles(),
        ];
    }

    private function countCacheFiles(): int
    {
        $pattern = rtrim($this->cacheDirectory, '/') . '/*.cache';
        $files = glob($pattern);

        return is_array($files) ? count($files) : 0;
    }
}
