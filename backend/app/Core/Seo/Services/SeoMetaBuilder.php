<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Seo\Services;

use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Builds SEO meta payload from content + settings (Iteration 23).
 *
 * @phpstan-type SeoMeta array{
 *     title: string,
 *     description: string,
 *     canonical: string,
 *     robots: string,
 *     openGraph: array{title: string, description: string, url: string, type: string, image: string},
 *     twitter: array{card: string, title: string, description: string, image: string},
 *     jsonLd: array<string, mixed>
 * }
 */
final class SeoMetaBuilder
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @return SeoMeta
     */
    public function buildForContent(Content $content, string $type, string $slug): array
    {
        $general = $this->settings->group('general');
        $seo = $this->settings->group('seo');

        $siteName = (string) ($general['siteName'] ?? 'PaginiumCMS');
        $siteUrl = rtrim((string) ($general['siteUrl'] ?? ''), '/');
        if ($siteUrl === '') {
            $siteUrl = 'http://localhost:3025';
        }

        $frontMatter = $content->getFrontMatter();
        $title = $this->resolveTitle($content, $siteName, $seo, $frontMatter);
        $description = $this->resolveDescription($content, $seo, $frontMatter);
        $canonical = $this->resolveCanonical($siteUrl, $type, $slug, $frontMatter);
        $image = $this->resolveImage($siteUrl, $seo, $frontMatter);
        $robots = $this->resolveRobots($seo, $frontMatter);
        $ogType = $type === 'article' ? 'article' : 'website';

        $openGraph = [
            'title' => $title,
            'description' => $description,
            'url' => $canonical,
            'type' => $ogType,
            'image' => $image,
        ];

        $twitter = [
            'card' => (string) ($seo['twitterCard'] ?? 'summary_large_image'),
            'title' => $title,
            'description' => $description,
            'image' => $image,
        ];

        $jsonLd = $type === 'article'
            ? $this->buildArticleJsonLd($content, $title, $description, $canonical, $image, $siteName)
            : $this->buildWebPageJsonLd($title, $description, $canonical, $siteName, $siteUrl);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'openGraph' => $openGraph,
            'twitter' => $twitter,
            'jsonLd' => $jsonLd,
        ];
    }

    /**
     * @param array<int|string, mixed> $seo
     * @param array<int|string, mixed> $frontMatter
     */
    private function resolveTitle(Content $content, string $siteName, array $seo, array $frontMatter): string
    {
        $custom = trim((string) ($frontMatter['seoTitle'] ?? $frontMatter['metaTitle'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $template = (string) ($seo['titleTemplate'] ?? '%title% | %siteName%');
        $title = $content->getTitle();

        return str_replace(['%title%', '%siteName%'], [$title, $siteName], $template);
    }

    /**
     * @param array<int|string, mixed> $seo
     * @param array<int|string, mixed> $frontMatter
     */
    private function resolveDescription(Content $content, array $seo, array $frontMatter): string
    {
        $custom = trim((string) ($frontMatter['seoDescription'] ?? $frontMatter['description'] ?? ''));
        if ($custom !== '') {
            return $this->truncate($custom, 300);
        }

        if ($content instanceof Article) {
            $excerpt = $content->getExcerpt(160);
            if ($excerpt !== '') {
                return $excerpt;
            }
        }

        $plain = trim(strip_tags($content->getContent()));
        if ($plain !== '') {
            return $this->truncate($plain, 160);
        }

        return $this->truncate((string) ($seo['defaultDescription'] ?? ''), 160);
    }

    /**
     * @param array<int|string, mixed> $frontMatter
     */
    private function resolveCanonical(string $siteUrl, string $type, string $slug, array $frontMatter): string
    {
        $custom = trim((string) ($frontMatter['canonical'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        if ($slug === 'home' || $slug === 'index') {
            return $siteUrl . '/';
        }

        if ($type === 'article') {
            return $siteUrl . '/blog/' . rawurlencode($slug);
        }

        return $siteUrl . '/' . rawurlencode($slug);
    }

    /**
     * @param array<int|string, mixed> $seo
     * @param array<int|string, mixed> $frontMatter
     */
    private function resolveImage(string $siteUrl, array $seo, array $frontMatter): string
    {
        $custom = trim((string) ($frontMatter['seoImage'] ?? $frontMatter['featuredImage'] ?? $frontMatter['featured_image'] ?? ''));
        if ($custom !== '') {
            return $this->absoluteUrl($siteUrl, $custom);
        }

        $default = trim((string) ($seo['defaultImage'] ?? ''));

        return $default !== '' ? $this->absoluteUrl($siteUrl, $default) : '';
    }

    /**
     * @param array<int|string, mixed> $seo
     * @param array<int|string, mixed> $frontMatter
     */
    private function resolveRobots(array $seo, array $frontMatter): string
    {
        if (($frontMatter['noIndex'] ?? false) === true || ($frontMatter['noindex'] ?? false) === true) {
            return 'noindex,nofollow';
        }

        if (($seo['allowSearchIndexing'] ?? true) === false) {
            return 'noindex,nofollow';
        }

        return (string) ($seo['robotsDefault'] ?? 'index,follow');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWebPageJsonLd(
        string $title,
        string $description,
        string $url,
        string $siteName,
        string $siteUrl
    ): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $siteUrl . '/',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildArticleJsonLd(
        Content $content,
        string $title,
        string $description,
        string $url,
        string $image,
        string $siteName
    ): array {
        $frontMatter = $content->getFrontMatter();
        $published = (string) ($frontMatter['date'] ?? $frontMatter['publishedAt'] ?? date('c'));

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title,
            'description' => $description,
            'url' => $url,
            'datePublished' => $published,
            'dateModified' => (string) ($frontMatter['updatedAt'] ?? $published),
            'author' => [
                '@type' => 'Person',
                'name' => $content->getAuthor() ?: 'Redakcia',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
            ],
        ];

        if ($image !== '') {
            $data['image'] = [$image];
        }

        return $data;
    }

    private function absoluteUrl(string $siteUrl, string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $siteUrl . (str_starts_with($path, '/') ? $path : '/' . $path);
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}
