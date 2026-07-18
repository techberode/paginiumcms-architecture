<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

/**
 * Špecializovaná cache pre flat-file obsah.
 *
 * Kľúče:
 * - content.pages.list.{filterHash}
 * - content.articles.list.{filterHash}
 * - content.page.{slug}
 * - content.article.{slug}
 *
 * Po každom zápise zavolaj invalidate* – žiadne globálne clear().
 */
class ContentCacheService
{
    private const TTL_LIST = 300;
    private const TTL_ITEM = 600;

    public function __construct(private CacheManager $cache)
    {
    }

    /**
     * Generácia zoznamov – pri invalidácii stačí increment gen (O(1)).
     */
    private function bumpListGeneration(string $entity): void
    {
        $this->cache->increment('content.' . $entity . '.list.gen', 1);
    }

    private function listGeneration(string $entity): int
    {
        return (int) $this->cache->get('content.' . $entity . '.list.gen', 0);
    }

    /**
     * @param array<int|string, mixed> $filters
 * @return array<int|string, mixed>
 */public function rememberPageList(array $filters, callable $loader): array
    {
        $gen = $this->listGeneration('pages');
        $key = 'content.pages.list.' . $gen . '.' . md5(json_encode($filters) ?: '');

        return $this->cache->rememberLocked($key, $loader, self::TTL_LIST);
    }

    /**
     * @param array<int|string, mixed> $filters
 * @return array<int|string, mixed>
 */public function rememberArticleList(array $filters, callable $loader): array
    {
        $gen = $this->listGeneration('articles');
        $key = 'content.articles.list.' . $gen . '.' . md5(json_encode($filters) ?: '');

        return $this->cache->rememberLocked($key, $loader, self::TTL_LIST);
    }

    public function rememberPage(string $slug, callable $loader): mixed
    {
        $key = 'content.page.' . $slug;

        return $this->cache->rememberLocked($key, $loader, self::TTL_ITEM);
    }

    public function rememberArticle(string $slug, callable $loader): mixed
    {
        $key = 'content.article.' . $slug;

        return $this->cache->rememberLocked($key, $loader, self::TTL_ITEM);
    }

    public function invalidatePage(?string $slug = null): void
    {
        $this->bumpListGeneration('pages');
        if ($slug !== null) {
            $this->cache->delete('content.page.' . $slug);
        }
    }

    public function invalidateArticle(?string $slug = null): void
    {
        $this->bumpListGeneration('articles');
        if ($slug !== null) {
            $this->cache->delete('content.article.' . $slug);
        }
    }

    /**
     * @param array<int|string, mixed> $filters
     * @return array{items: array<int|string, mixed>, total: int}
     */
    public function rememberPageListPaginated(array $filters, callable $loader): array
    {
        $gen = $this->listGeneration('pages');
        $key = 'content.pages.paginated.' . $gen . '.' . md5(json_encode($filters) ?: '');

        return $this->cache->rememberLocked($key, $loader, self::TTL_LIST);
    }

    /**
     * @param array<int|string, mixed> $filters
     * @return array{items: array<int|string, mixed>, total: int}
     */
    public function rememberArticleListPaginated(array $filters, callable $loader): array
    {
        $gen = $this->listGeneration('articles');
        $key = 'content.articles.paginated.' . $gen . '.' . md5(json_encode($filters) ?: '');

        return $this->cache->rememberLocked($key, $loader, self::TTL_LIST);
    }

    public function invalidateSearch(): void
    {
        $this->bumpListGeneration('pages');
        $this->bumpListGeneration('articles');
    }

    /**
     * Kompletná invalidácia cache obsahu (po deployi alebo oprave cache bugov).
     */
    public function purgeAll(): void
    {
        $this->invalidatePage();
        $this->invalidateArticle();
        $this->invalidateSearch();
    }
}
