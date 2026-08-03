<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

/**
 * Canonical cache tag names for deterministic invalidation (Iteration 69).
 *
 * Tags group related cache keys; drivers map tags to stored keys via tagKey().
 */
final class CacheTagRegistry
{
    public static function pagesList(): string
    {
        return 'content:pages:list';
    }

    public static function articlesList(): string
    {
        return 'content:articles:list';
    }

    public static function page(string $slug): string
    {
        return 'content:page:' . $slug;
    }

    public static function article(string $slug): string
    {
        return 'content:article:' . $slug;
    }

    public static function feeds(): string
    {
        return 'content:feeds';
    }

    /**
     * @return list<string>
     */
    public static function invalidatePageTags(?string $slug = null): array
    {
        $tags = [self::pagesList(), self::feeds()];
        if ($slug !== null && $slug !== '') {
            $tags[] = self::page($slug);
        }

        return $tags;
    }

    /**
     * @return list<string>
     */
    public static function invalidateArticleTags(?string $slug = null): array
    {
        $tags = [self::articlesList(), self::feeds()];
        if ($slug !== null && $slug !== '') {
            $tags[] = self::article($slug);
        }

        return $tags;
    }
}
