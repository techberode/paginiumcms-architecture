<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\ContentRepository;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Services\JsonContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownParser;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\PaginationQuery;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class ContentRepositoryTest extends TestCase
{
    private ContentRepository $repository;
    private ContentIndexService $index;
    private string $root;

    protected function setUp(): void
    {
        $structure = [
            'content' => [
                'pages' => [
                    'home.md' => "---\ntitle: Home\nslug: home\nstatus: published\n---\n# Welcome",
                    'about.md' => "---\ntitle: About\nslug: about\nstatus: published\n---\n# About Us",
                    'draft-page.md' => "---\ntitle: Draft\nslug: draft-page\nstatus: draft\n---\n# Draft",
                ],
                'blog' => [
                    '2024-01-01-test.md' => "---\ntitle: Test Article\nslug: test-article\nstatus: published\nauthor: John\n---\n# Test Content",
                ],
            ],
        ];

        vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $validator = new FileValidator($this->root . '/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $frontMatterParser = new FrontMatterParser();
        $contentParser = new MarkdownContentParser();
        $markdownParser = new MarkdownParser($frontMatterParser, $contentParser);
        $markdownStorage = new MarkdownContentStorage($markdownParser);
        $jsonStorage = new JsonContentStorage($contentParser);
        $this->index = new ContentIndexService($reader, 'data/index/content.json');

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => $default);

        $this->repository = new ContentRepository(
            $reader,
            $writer,
            $this->index,
            $markdownStorage,
            $jsonStorage,
            $settings
        );
    }

    public function testFindByPathExisting(): void
    {
        $content = $this->repository->findByPath('pages/home.md');
        $this->assertNotNull($content);
        $this->assertEquals('Home', $content->getTitle());
        $this->assertEquals('home', $content->getSlug());
        $this->assertStringContainsString('Welcome', $content->getContent());
    }

    public function testFindBySlugUsesIndex(): void
    {
        $this->index->rebuild($this->repository);
        $content = $this->repository->findBySlug('about', 'page');
        $this->assertNotNull($content);
        $this->assertEquals('About', $content->getTitle());
    }

    public function testFindPagesPaginatedFiltersStatus(): void
    {
        $this->index->rebuild($this->repository);
        $query = new PaginationQuery(1, 10, '', '-updatedAt', ['status' => 'published']);
        $result = $this->repository->findPagesPaginated($query);

        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $result['items']);
    }

    public function testFindPagesPaginatedSearch(): void
    {
        $this->index->rebuild($this->repository);
        $query = new PaginationQuery(1, 10, 'about', '-updatedAt', []);
        $result = $this->repository->findPagesPaginated($query);

        $this->assertSame(1, $result['total']);
        $this->assertSame('about', $result['items'][0]->getSlug());
    }

    public function testSaveUpdatesIndex(): void
    {
        $this->index->rebuild($this->repository);

        $page = new Page();
        $page->setTitle('Indexed Page');
        $page->setSlug('indexed-page');
        $page->setContent('# Indexed');
        $page->setStatus('published');

        $this->repository->save($page);

        $query = new PaginationQuery(1, 20, 'indexed', '-updatedAt', []);
        $result = $this->repository->findPagesPaginated($query);
        $this->assertSame(1, $result['total']);
    }

    public function testDeleteRemovesFromIndex(): void
    {
        $this->index->rebuild($this->repository);
        $page = $this->repository->findByPath('pages/home.md');
        $this->assertNotNull($page);

        $this->repository->delete($page, true);

        $query = new PaginationQuery(1, 20, 'home', '-updatedAt', []);
        $result = $this->repository->findPagesPaginated($query);
        $this->assertSame(0, $result['total']);
    }

    public function testFindAllArticles(): void
    {
        $articles = $this->repository->findAllArticles();
        $this->assertCount(1, $articles);
        $this->assertSame('Test Article', $articles[0]->getTitle());
    }

    public function testCountPages(): void
    {
        $this->assertSame(3, $this->repository->count('page'));
    }
}
