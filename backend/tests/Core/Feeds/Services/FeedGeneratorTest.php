<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Feeds\Services;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Feeds\Services\FeedGenerator;
use PaginiumCMS\Core\Feeds\Services\SitemapGenerator;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class FeedGeneratorTest extends TestCase
{
    private ContentIndexService $index;
    /** @var SettingsRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private SettingsRepositoryInterface $settings;

    protected function setUp(): void
    {
        vfsStream::setup('storage');
        $root = vfsStream::url('storage');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $this->settings = $this->createMock(SettingsRepositoryInterface::class);
        $this->settings->method('get')->willReturn('sk');
        $normalizer = new LocalizedContentNormalizer($this->settings);
        $staleness = new ContentStalenessService($this->settings);
        $this->index = new ContentIndexService($reader, $normalizer, $staleness, 'data/index/content.json');
    }

    public function testRssContainsOnlyPublishedArticles(): void
    {
        $published = new Article();
        $published->setSlug('published-post');
        $published->setFrontMatter([
            'title' => 'Published Post',
            'slug' => 'published-post',
            'status' => 'published',
            'updatedAt' => '2026-07-17T10:00:00+02:00',
        ]);
        $published->setContent('Hello RSS');
        $this->index->upsertFromContent($published, 'article');

        $draft = new Article();
        $draft->setSlug('draft-post');
        $draft->setFrontMatter([
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'status' => 'draft',
        ]);
        $draft->setContent('Hidden');
        $this->index->upsertFromContent($draft, 'article');

        $this->settings->method('group')->willReturnMap([
            ['feeds', [
                'enabled' => true,
                'title' => '',
                'description' => 'Test feed',
                'itemsLimit' => 20,
                'includePages' => true,
                'includeArticles' => true,
            ]],
            ['general', [
                'siteName' => 'Test Site',
                'siteUrl' => 'https://example.com',
                'siteDescription' => 'Desc',
            ]],
        ]);

        $generator = new FeedGenerator($this->index, $this->settings);
        $xml = $generator->generate();

        $this->assertStringContainsString('<rss version="2.0">', $xml);
        $this->assertStringContainsString('Published Post', $xml);
        $this->assertStringContainsString('https://example.com/blog/published-post', $xml);
        $this->assertStringNotContainsString('Draft Post', $xml);
    }

    public function testSitemapIncludesPublishedPage(): void
    {
        $page = new Page();
        $page->setSlug('about');
        $page->setFrontMatter([
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'updatedAt' => '2026-07-17T09:00:00+02:00',
        ]);
        $page->setContent('# About');
        $this->index->upsertFromContent($page, 'page');

        $this->settings->method('group')->willReturnMap([
            ['feeds', [
                'enabled' => true,
                'itemsLimit' => 20,
                'includePages' => true,
                'includeArticles' => true,
            ]],
            ['general', ['siteUrl' => 'https://example.com']],
        ]);

        $generator = new SitemapGenerator($this->index, $this->settings);
        $xml = $generator->generate();

        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('https://example.com/about', $xml);
    }
}
