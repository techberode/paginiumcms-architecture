<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Models;

use PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry;
use PHPUnit\Framework\TestCase;

final class ContentIndexEntryTest extends TestCase
{
    public function testMatchesPublishedWhenAnyLocaleIsPublished(): void
    {
        $entry = new ContentIndexEntry(
            slug: 'about',
            type: 'page',
            title: 'About',
            status: 'draft',
            author: '',
            path: 'pages/about.md',
            excerpt: '',
            tags: [],
            updatedAt: '2026-08-08T10:00:00+00:00',
            createdAt: '2026-08-08T10:00:00+00:00',
            defaultLocale: 'sk',
            locales: ['sk', 'en'],
            localeStatus: ['sk' => 'published', 'en' => 'draft'],
        );

        $this->assertTrue($entry->matchesStatusFilter('published'));
        $this->assertTrue($entry->matchesStatusFilter('published', 'sk'));
        $this->assertFalse($entry->matchesStatusFilter('published', 'en'));
        $this->assertTrue($entry->matchesStatusFilter('draft', 'en'));
    }
}
