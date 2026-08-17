<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\PaginationQuery;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class ContentIndexCategoryFilterTest extends TestCase
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

    public function testListDistinctCategoriesAndFilterByCategory(): void
    {
        $this->index->upsertFromContent($this->article('alpha', 'news'), 'article');
        $this->index->upsertFromContent($this->article('beta', 'security'), 'article');
        $this->index->upsertFromContent($this->article('gamma', 'news'), 'article');

        $categories = $this->index->listDistinctCategories('article', ['status' => 'published']);
        $this->assertSame(['news', 'security'], $categories);

        $filtered = $this->index->query('article', new PaginationQuery(
            1,
            20,
            '',
            '-updatedAt',
            ['status' => 'published', 'category' => 'news']
        ));

        $this->assertSame(['alpha', 'gamma'], array_map(
            static fn (\PaginiumCMS\Core\FlatFile\Models\ContentIndexEntry $entry): string => $entry->slug,
            $filtered['entries']
        ));
    }

    private function article(string $slug, string $category): Article
    {
        $article = new Article();
        $article->setSlug($slug);
        $article->setFrontMatter([
            'title' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'published',
            'category' => $category,
            'updatedAt' => '2026-08-01T10:00:00+02:00',
            'createdAt' => '2026-08-01T10:00:00+02:00',
        ]);
        $article->setContent('Body');

        return $article;
    }
}
