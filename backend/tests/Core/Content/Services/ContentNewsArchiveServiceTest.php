<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content\Services;

use DateTimeImmutable;
use PaginiumCMS\Core\Content\Services\ContentNewsArchiveService;
use PaginiumCMS\Core\Content\Services\NewsArchivePolicy;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

final class ContentNewsArchiveServiceTest extends TestCase
{
    public function testMoveArticleToArchiveBySlug(): void
    {
        $slug = 'news-archive-manual-' . uniqid('', true);
        $article = new Article();
        $article->setSlug($slug);
        $article->setTitle('News item');
        $article->setStatus('published');
        $article->setCategory(NewsArchivePolicy::CATEGORY_NEWS);
        $article->setContent('# Hello');
        $article->setFrontMatter([
            'title' => 'News item',
            'slug' => $slug,
            'status' => 'published',
            'category' => NewsArchivePolicy::CATEGORY_NEWS,
            'createdAt' => (new DateTimeImmutable('-2 days'))->format('c'),
        ]);

        $repository = $this->app->getContainer()->get(\PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface::class);
        $repository->save($article);

        $service = $this->app->getContainer()->get(ContentNewsArchiveService::class);
        $this->assertTrue($service->moveArticleToArchiveBySlug($slug), 'moveArticleToArchiveBySlug should succeed');

        $saved = $repository->findBySlug($slug, 'article');
        $this->assertNotNull($saved);
        $this->assertSame(NewsArchivePolicy::CATEGORY_ARCHIVE, $saved->getCategory());
        $this->assertSame('published', $saved->getStatus());
    }
}

final class NewsArchivePolicyTest extends PHPUnitTestCase
{
    public function testDetectsRetentionDueForNews(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('scheduler')->willReturn([
            'newsAutoArchiveEnabled' => true,
            'newsRetentionDaysDefault' => 21,
        ]);

        $policy = new NewsArchivePolicy($settings);
        $article = new Article();
        $article->setStatus('published');
        $article->setCategory(NewsArchivePolicy::CATEGORY_NEWS);
        $article->setFrontMatter([
            'date' => (new DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s'),
        ]);

        $this->assertTrue($policy->isNewsArchiveDue($article, new DateTimeImmutable('today')));
    }

    public function testClampsPerArticleRetentionToFortyFive(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([]);

        $policy = new NewsArchivePolicy($settings);
        $this->assertSame(45, $policy->clampRetentionDays(99));
        $this->assertSame(21, $policy->defaultRetentionDays());
    }
}
