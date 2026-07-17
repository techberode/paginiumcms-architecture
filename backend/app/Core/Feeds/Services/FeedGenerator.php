<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Feeds\Services;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Generates RSS 2.0 feed XML from published content (Iteration 22).
 */
final class FeedGenerator
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
            return $this->emptyFeed();
        }

        $general = $this->settings->group('general');
        $siteName = htmlspecialchars((string) ($feeds['title'] ?: $general['siteName'] ?? 'PaginiumCMS'), ENT_XML1);
        $siteUrl = rtrim((string) ($general['siteUrl'] ?? ''), '/');
        if ($siteUrl === '') {
            $siteUrl = 'http://localhost:3025';
        }
        $description = htmlspecialchars(
            (string) ($feeds['description'] ?: $general['siteDescription'] ?? ''),
            ENT_XML1
        );
        $limit = min(100, max(1, (int) ($feeds['itemsLimit'] ?? 20)));

        $entries = [];
        if (($feeds['includeArticles'] ?? true) !== false) {
            $query = new PaginationQuery(1, $limit, '', '-updatedAt', ['status' => 'published']);
            $result = $this->index->query('article', $query);
            $entries = array_merge($entries, $result['entries']);
        }

        usort(
            $entries,
            static fn (ContentIndexEntry $a, ContentIndexEntry $b): int => strcmp($b->updatedAt, $a->updatedAt)
        );
        $entries = array_slice($entries, 0, $limit);

        $items = '';
        foreach ($entries as $entry) {
            $items .= $this->renderItem($entry, $siteUrl);
        }

        $feedUrl = htmlspecialchars($siteUrl . '/feed.xml', ENT_XML1);
        $updated = $entries !== [] ? $entries[0]->updatedAt : date('c');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>{$siteName}</title>
    <link>{$this->xml($siteUrl)}</link>
    <description>{$description}</description>
    <language>sk</language>
    <lastBuildDate>{$this->rssDate($updated)}</lastBuildDate>
    <atom:link href="{$feedUrl}" rel="self" type="application/rss+xml" xmlns:atom="http://www.w3.org/2005/Atom"/>
    {$items}
  </channel>
</rss>
XML;
    }

    private function renderItem(ContentIndexEntry $entry, string $siteUrl): string
    {
        $link = $siteUrl . ($entry->type === 'article' ? '/blog/' . $entry->slug : '/' . $entry->slug);
        if ($entry->slug === 'home' || $entry->slug === 'index') {
            $link = $siteUrl . '/';
        }

        $title = htmlspecialchars($entry->title, ENT_XML1);
        $description = htmlspecialchars($entry->excerpt, ENT_XML1);
        $pubDate = $this->rssDate($entry->updatedAt);
        $guid = htmlspecialchars($link, ENT_XML1);

        return <<<XML
    <item>
      <title>{$title}</title>
      <link>{$this->xml($link)}</link>
      <guid isPermaLink="true">{$guid}</guid>
      <description>{$description}</description>
      <pubDate>{$pubDate}</pubDate>
    </item>
XML;
    }

    private function emptyFeed(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>PaginiumCMS</title>
    <description>Feed disabled</description>
  </channel>
</rss>
XML;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1);
    }

    private function rssDate(string $iso): string
    {
        $timestamp = strtotime($iso);

        return $timestamp !== false ? gmdate('D, d M Y H:i:s', $timestamp) . ' GMT' : gmdate('D, d M Y H:i:s') . ' GMT';
    }
}
