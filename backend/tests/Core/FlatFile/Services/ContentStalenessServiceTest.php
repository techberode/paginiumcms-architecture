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

final class ContentStalenessServiceTest extends TestCase
{
    private ContentStalenessService $staleness;

    protected function setUp(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => match ($key) {
            'content.staleReviewMonths' => 12,
            default => $default,
        });
        $this->staleness = new ContentStalenessService($settings);
    }

    public function testDraftContentIsNeverStale(): void
    {
        $result = $this->staleness->evaluate(
            'draft',
            '',
            '2020-01-01T00:00:00+00:00',
            '2020-01-01T00:00:00+00:00',
            new \DateTimeImmutable('2026-08-15')
        );

        $this->assertFalse($result['isStale']);
        $this->assertNull($result['monthsSinceReview']);
    }

    public function testPublishedContentOlderThanThresholdIsStale(): void
    {
        $result = $this->staleness->evaluate(
            'published',
            '',
            '2020-01-01T00:00:00+00:00',
            '2020-01-01T00:00:00+00:00',
            new \DateTimeImmutable('2026-08-15')
        );

        $this->assertTrue($result['isStale']);
        $this->assertGreaterThan(12, $result['monthsSinceReview']);
    }

    public function testRecentReviewResetsStaleness(): void
    {
        $result = $this->staleness->evaluate(
            'published',
            '2026-07-01T00:00:00+00:00',
            '2020-01-01T00:00:00+00:00',
            '2020-01-01T00:00:00+00:00',
            new \DateTimeImmutable('2026-08-15')
        );

        $this->assertFalse($result['isStale']);
        $this->assertSame(1, $result['monthsSinceReview']);
    }

    public function testDisabledThresholdNeverFlagsStale(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn(0);
        $service = new ContentStalenessService($settings);

        $result = $service->evaluate(
            'published',
            '',
            '2020-01-01T00:00:00+00:00',
            '2020-01-01T00:00:00+00:00',
            new \DateTimeImmutable('2026-08-15')
        );

        $this->assertFalse($result['isStale']);
    }
}

final class ContentIndexStaleFilterTest extends TestCase
{
    private ContentIndexService $index;

    protected function setUp(): void
    {
        vfsStream::setup('storage');
        $root = vfsStream::url('storage');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => match ($key) {
            'content.staleReviewMonths' => 12,
            default => $default,
        });
        $normalizer = new LocalizedContentNormalizer($settings);
        $staleness = new ContentStalenessService($settings);
        $this->index = new ContentIndexService($reader, $normalizer, $staleness, 'data/index/content.json');
    }

    public function testStaleFilterReturnsOnlyPublishedItemsPastThreshold(): void
    {
        $stalePage = new Page();
        $stalePage->setSlug('stale-page');
        $stalePage->setFrontMatter([
            'title' => 'Stale Page',
            'slug' => 'stale-page',
            'status' => 'published',
            'updatedAt' => '2020-01-01T00:00:00+00:00',
        ]);
        $stalePage->setContent('# Stale');
        $this->index->upsertFromContent($stalePage, 'page');

        $freshPage = new Page();
        $freshPage->setSlug('fresh-page');
        $freshPage->setFrontMatter([
            'title' => 'Fresh Page',
            'slug' => 'fresh-page',
            'status' => 'published',
            'updatedAt' => '2026-07-01T00:00:00+00:00',
        ]);
        $freshPage->setContent('# Fresh');
        $this->index->upsertFromContent($freshPage, 'page');

        $draft = new Article();
        $draft->setSlug('old-draft');
        $draft->setFrontMatter([
            'title' => 'Old Draft',
            'slug' => 'old-draft',
            'status' => 'draft',
            'updatedAt' => '2020-01-01T00:00:00+00:00',
        ]);
        $draft->setContent('Draft');
        $this->index->upsertFromContent($draft, 'article');

        $matches = $this->index->countMatching('page', ['stale' => '1']);

        $this->assertSame(1, $matches);
    }
}
