<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Feeds\Services;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Generates sitemap.xml from published content index (Iteration 22).
 */
final class SitemapGenerator
{
    public function __construct(
        private ContentIndexService $index,
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function generate(): string
    {
        $feeds = $this->settings->group('feeds');
        if (($feeds['enabled'] ?? true) === false) {
            return $this->emptySitemap();
        }

        $general = $this->settings->group('general');
        $siteUrl = rtrim((string) ($general['siteUrl'] ?? ''), '/');
        if ($siteUrl === '') {
            $siteUrl = 'http://localhost:3025';
        }

        $entries = [];
        $limit = min(500, max(1, (int) ($feeds['itemsLimit'] ?? 20)) * 10);

        if (($feeds['includePages'] ?? true) !== false) {
            $pages = $this->index->query(
                'page',
                new PaginationQuery(1, $limit, '', '-updatedAt', ['status' => 'published'])
            );
            $entries = array_merge($entries, $pages['entries']);
        }

        if (($feeds['includeArticles'] ?? true) !== false) {
            $articles = $this->index->query(
                'article',
                new PaginationQuery(1, $limit, '', '-updatedAt', ['status' => 'published'])
            );
            $entries = array_merge($entries, $articles['entries']);
        }

        $urls = '';
        $seen = [];
        foreach ($entries as $entry) {
            $loc = $this->buildUrl($entry, $siteUrl);
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $lastmod = htmlspecialchars(substr($entry->updatedAt, 0, 10), ENT_XML1);
            $locXml = htmlspecialchars($loc, ENT_XML1);
            $urls .= "  <url>\n    <loc>{$locXml}</loc>\n    <lastmod>{$lastmod}</lastmod>\n  </url>\n";
        }

        if ($urls === '') {
            $home = htmlspecialchars($siteUrl . '/', ENT_XML1);
            $today = date('Y-m-d');
            $urls = "  <url>\n    <loc>{$home}</loc>\n    <lastmod>{$today}</lastmod>\n  </url>\n";
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$urls}</urlset>
XML;
    }

    private function buildUrl(ContentIndexEntry $entry, string $siteUrl): string
    {
        if ($entry->slug === 'home' || $entry->slug === 'index') {
            return $siteUrl . '/';
        }

        if ($entry->type === 'article') {
            return $siteUrl . '/blog/' . $entry->slug;
        }

        return $siteUrl . '/' . $entry->slug;
    }

    private function emptySitemap(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>
XML;
    }
}
