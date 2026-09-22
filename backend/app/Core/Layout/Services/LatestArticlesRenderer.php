<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Static HTML for [latest-articles] — rolling headline strip with links to blog posts.
 */
final class LatestArticlesRenderer
{
    /**
     * @param array<string, string>              $attrs
     * @param list<array{slug: string, title: string}> $articles
     */
    public static function render(array $attrs, array $articles): string
    {
        $label = self::text($attrs['label'] ?? 'Novinky');
        $speed = self::speedClass($attrs['speed'] ?? 'normal');

        if ($articles === []) {
            return '<aside class="pg-news-ticker pg-news-ticker-empty" aria-label="' . $label . '">'
                . '<span class="pg-news-ticker-label">' . $label . '</span>'
                . '<p class="pg-news-ticker-empty-msg">' . self::text($attrs['empty'] ?? '') . '</p>'
                . '</aside>';
        }

        $itemsHtml = '';
        foreach ($articles as $article) {
            $title = self::text($article['title']);
            $href = '/blog/' . rawurlencode($article['slug']);
            $itemsHtml .= '<li class="pg-news-ticker-item">'
                . '<a class="pg-news-ticker-link" href="' . $href . '">' . $title . '</a>'
                . '</li>';
        }

        // Duplicate track for seamless CSS marquee loop.
        $track = '<ul class="pg-news-ticker-track">' . $itemsHtml . $itemsHtml . '</ul>';

        return '<aside class="pg-news-ticker ' . $speed . '" aria-label="' . $label . '">'
            . '<span class="pg-news-ticker-label">' . $label . '</span>'
            . '<div class="pg-news-ticker-viewport">' . $track . '</div>'
            . '</aside>';
    }

    private static function speedClass(string $speed): string
    {
        return match (strtolower(trim($speed))) {
            'slow' => 'pg-news-ticker-speed-slow',
            'fast' => 'pg-news-ticker-speed-fast',
            default => 'pg-news-ticker-speed-normal',
        };
    }

    private static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
