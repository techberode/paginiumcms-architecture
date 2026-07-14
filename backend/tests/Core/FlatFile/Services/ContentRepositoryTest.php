<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Services\ContentRepository;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownParser;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class ContentRepositoryTest extends TestCase
{
    private ContentRepository $repository;
    private string $root;

    protected function setUp(): void
    {
        $structure = [
            'content' => [
                'pages' => [
                    'home.md' => "---\ntitle: Home\nslug: home\nstatus: published\n---\n# Welcome",
                    'about.md' => "---\ntitle: About\nslug: about\nstatus: published\n---\n# About Us",
                ],
                'blog' => [
                    '2024-01-01-test.md' => "---\ntitle: Test Article\nslug: test-article\nstatus: published\nauthor: John\n---\n# Test Content",
                ],
            ],
        ];

        $root = vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $validator = new FileValidator($this->root . '/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $frontMatterParser = new FrontMatterParser();
        $contentParser = new MarkdownContentParser();
        $markdownParser = new MarkdownParser($frontMatterParser, $contentParser);

        $this->repository = new ContentRepository($reader, $writer, $markdownParser);
    }

    public function testFindByPathExisting(): void
    {
        $content = $this->repository->findByPath('pages/home.md');
        $this->assertNotNull($content);
        $this->assertEquals('Home', $content->getTitle());
        $this->assertEquals('home', $content->getSlug());
        $this->assertStringContainsString('Welcome', $content->getContent());
    }

    public function testFindByPathNonExisting(): void
    {
        $content = $this->repository->findByPath('pages/missing.md');
        $this->assertNull($content);
    }

    public function testFindBySlug(): void
    {
        $content = $this->repository->findBySlug('about', 'page');
        $this->assertNotNull($content);
        $this->assertEquals('About', $content->getTitle());
    }

    public function testFindAllPages(): void
    {
        $pages = $this->repository->findAllPages();
        $this->assertCount(2, $pages);
        $this->assertInstanceOf(Page::class, $pages[0]);
    }

    public function testFindAllPagesWithFilter(): void
    {
        $pages = $this->repository->findAllPages(['status' => 'published']);
        $this->assertCount(2, $pages);
    }

    public function testFindAllArticles(): void
    {
        $articles = $this->repository->findAllArticles();
        $this->assertCount(1, $articles);
        $this->assertInstanceOf(Article::class, $articles[0]);
        $this->assertEquals('Test Article', $articles[0]->getTitle());
    }

    public function testSaveNewPage(): void
    {
        $page = new Page();
        $page->setTitle('New Page');
        $page->setSlug('new-page');
        $page->setContent('# New Content');
        $page->setStatus('draft');

        $this->repository->save($page);

        $this->assertFileExists($this->root . '/content/pages/new-page.md');
        $saved = $this->repository->findByPath('pages/new-page.md');
        $this->assertNotNull($saved);
        $this->assertEquals('New Page', $saved->getTitle());
    }

    public function testCountPages(): void
    {
        $count = $this->repository->count('page');
        $this->assertEquals(2, $count);
    }

    public function testCountArticles(): void
    {
        $count = $this->repository->count('article');
        $this->assertEquals(1, $count);
    }

    public function testDeletePage(): void
    {
        $page = $this->repository->findByPath('pages/home.md');
        $this->assertNotNull($page);

        $this->repository->delete($page);

        $this->assertFileDoesNotExist($this->root . '/content/pages/home.md');
        $deleted = $this->repository->findByPath('pages/home.md');
        $this->assertNull($deleted);
    }
}
