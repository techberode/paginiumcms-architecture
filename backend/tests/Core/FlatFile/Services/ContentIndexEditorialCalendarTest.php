<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class ContentIndexEditorialCalendarTest extends TestCase
{
    private ContentIndexService $index;

    protected function setUp(): void
    {
        vfsStream::setup('storage');
        $root = vfsStream::url('storage');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $normalizer = new LocalizedContentNormalizer($settings);
        $staleness = new ContentStalenessService($settings);
        $this->index = new ContentIndexService($reader, $normalizer, $staleness, 'data/index/content.json');
    }

    public function testQueryEditorialCalendarReturnsScheduledAndPublishedInRange(): void
    {
        $scheduled = new Article();
        $scheduled->setSlug('launch-post');
        $scheduled->setFrontMatter([
            'title' => 'Launch Post',
            'slug' => 'launch-post',
            'status' => 'scheduled',
            'scheduledAt' => '2026-08-15T09:00:00+02:00',
            'updatedAt' => '2026-08-01T10:00:00+02:00',
        ]);
        $scheduled->setContent('Scheduled body');
        $this->index->upsertFromContent($scheduled, 'article');

        $published = new Page();
        $published->setSlug('about-us');
        $published->setFrontMatter([
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => 'published',
            'createdAt' => '2026-08-10T12:00:00+02:00',
            'updatedAt' => '2026-08-12T12:00:00+02:00',
        ]);
        $published->setContent('# About');
        $this->index->upsertFromContent($published, 'page');

        $outside = new Article();
        $outside->setSlug('old-post');
        $outside->setFrontMatter([
            'title' => 'Old Post',
            'slug' => 'old-post',
            'status' => 'published',
            'createdAt' => '2026-07-01T12:00:00+02:00',
        ]);
        $outside->setContent('Old');
        $this->index->upsertFromContent($outside, 'article');

        $entries = $this->index->queryEditorialCalendar('2026-08-01', '2026-08-31');

        $this->assertCount(2, $entries);
        $this->assertSame('about-us', $entries[0]->slug);
        $this->assertSame('2026-08-10', $entries[0]->calendarDate());
        $this->assertSame('launch-post', $entries[1]->slug);
        $this->assertSame('2026-08-15', $entries[1]->calendarDate());
    }

    public function testQueryEditorialCalendarFiltersByTypeAndTag(): void
    {
        $article = new Article();
        $article->setSlug('tagged-post');
        $article->setFrontMatter([
            'title' => 'Tagged Post',
            'slug' => 'tagged-post',
            'status' => 'published',
            'createdAt' => '2026-08-05T12:00:00+02:00',
            'tags' => ['news', 'featured'],
        ]);
        $article->setContent('Tagged');
        $this->index->upsertFromContent($article, 'article');

        $page = new Page();
        $page->setSlug('home');
        $page->setFrontMatter([
            'title' => 'Home',
            'slug' => 'home',
            'status' => 'published',
            'createdAt' => '2026-08-06T12:00:00+02:00',
        ]);
        $page->setContent('# Home');
        $this->index->upsertFromContent($page, 'page');

        $articlesOnly = $this->index->queryEditorialCalendar('2026-08-01', '2026-08-31', 'article');
        $this->assertCount(1, $articlesOnly);
        $this->assertSame('tagged-post', $articlesOnly[0]->slug);

        $tagged = $this->index->queryEditorialCalendar('2026-08-01', '2026-08-31', null, ['tag' => 'featured']);
        $this->assertCount(1, $tagged);
        $this->assertSame('tagged-post', $tagged[0]->slug);
    }

    public function testQueryEditorialCalendarReturnsEmptyForInvalidRange(): void
    {
        $this->assertSame([], $this->index->queryEditorialCalendar('2026-08-31', '2026-08-01'));
        $this->assertSame([], $this->index->queryEditorialCalendar('invalid', '2026-08-01'));
    }
}
