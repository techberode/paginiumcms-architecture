<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Content\Services\BlogSidebarService;
use PaginiumCMS\Core\Content\Services\CategoryCatalogSeeder;
use PaginiumCMS\Core\Content\Services\CategoryRepository;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\PaginationQuery;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class BlogSidebarServiceTest extends TestCase
{
    private CategoryRepository $categories;

    private CategoryCatalogSeeder $categorySeeder;

    protected function setUp(): void
    {
        vfsStream::setup('storage');
        $root = vfsStream::url('storage');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $this->categories = new CategoryRepository($reader, $writer);
        $this->categorySeeder = new CategoryCatalogSeeder($this->categories);
    }

    public function testBuildPublicPayloadRespectsSettings(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('content')->willReturn([
            'blogSidebarEnabled' => true,
            'blogSidebarPlacement' => 'left',
            'blogSidebarShowTags' => true,
            'blogSidebarShowCategories' => false,
            'blogSidebarShowLatest' => false,
            'blogSidebarShowPopular' => false,
        ]);

        $repository = $this->createMock(ContentRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('listDistinctTags')
            ->with('article', ['status' => 'published'])
            ->willReturn(['news', 'security']);

        $reporter = $this->createMock(ReporterInterface::class);

        $service = new BlogSidebarService(
            $settings,
            $repository,
            $reporter,
            $this->categories,
            $this->categorySeeder
        );
        $payload = $service->buildPublicPayload();

        $this->assertTrue($payload['enabled']);
        $this->assertSame('left', $payload['placement']);
        $this->assertSame(['news', 'security'], $payload['tags']);
        $this->assertSame([], $payload['latest']);
        $this->assertSame([], $payload['popular']);
    }

    public function testFindArticlesByPopularityOrdersByViews(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([]);

        $popular = $this->createArticle('alpha', 'Alpha');
        $quiet = $this->createArticle('beta', 'Beta');

        $repository = $this->createMock(ContentRepositoryInterface::class);
        $repository->method('findAllArticles')->willReturn([$quiet, $popular]);
        $repository->method('findBySlug')->willReturnCallback(
            static fn (string $slug, string $type): ?Article => match ($slug) {
                'alpha' => $popular,
                'beta' => $quiet,
                default => null,
            }
        );

        $reporter = $this->createMock(ReporterInterface::class);
        $reporter->method('getTopArticles')->willReturn([
            ['uri' => '/blog/alpha', 'views' => 12, 'title' => 'Alpha'],
            ['uri' => '/blog/beta', 'views' => 3, 'title' => 'Beta'],
        ]);

        $service = new BlogSidebarService(
            $settings,
            $repository,
            $reporter,
            $this->categories,
            $this->categorySeeder
        );
        $result = $service->findArticlesByPopularity(new PaginationQuery(1, 10, '', '-popular', ['status' => 'published']));

        $this->assertSame(['alpha', 'beta'], array_map(
            static fn (Article $article): string => $article->getSlug(),
            $result['items']
        ));
    }

    private function createArticle(string $slug, string $title): Article
    {
        $article = new Article();
        $article->setPath('articles/' . $slug . '.md');
        $article->setFrontMatter([
            'slug' => $slug,
            'title' => $title,
            'status' => 'published',
            'createdAt' => '2026-08-01T10:00:00+02:00',
        ]);
        $article->setContent('Body');

        return $article;
    }
}
