<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\HybridEngine\QueryIndex\JsonQueryIndex;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexPaths;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRebuilder;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexSqliteStore;
use PaginiumCMS\Core\HybridEngine\QueryIndex\SqliteQueryIndex;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\PaginationQuery;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class SqliteQueryIndexTest extends TestCase
{
    private string $baseDir;

    private ContentIndexService $contentIndex;

    private JsonQueryIndex $jsonIndex;

    private SqliteQueryIndex $sqliteIndex;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available.');
        }

        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_sqlidx_' . uniqid('', true);
        mkdir($this->baseDir . '/data/index', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $this->contentIndex = new ContentIndexService(
            $reader,
            new LocalizedContentNormalizer($settings),
            new ContentStalenessService($settings),
            'data/index/content.json'
        );
        $this->jsonIndex = new JsonQueryIndex($this->contentIndex);

        file_put_contents(
            $this->baseDir . '/data/index/content.json',
            JsonHelper::encode([
                'version' => 1,
                'items' => [
                    [
                        'type' => 'article',
                        'slug' => 'hello-world',
                        'title' => 'Hello World',
                        'excerpt' => 'Intro text',
                        'tags' => ['news', 'launch'],
                        'category' => 'blog',
                        'author' => 'Admin',
                        'status' => 'published',
                        'createdAt' => '2026-01-01',
                        'updatedAt' => '2026-01-02',
                        'path' => 'blog/hello-world.md',
                    ],
                    [
                        'type' => 'article',
                        'slug' => 'draft-note',
                        'title' => 'Draft Note',
                        'excerpt' => '',
                        'tags' => [],
                        'category' => '',
                        'author' => 'Admin',
                        'status' => 'draft',
                        'createdAt' => '2026-01-03',
                        'updatedAt' => '2026-01-03',
                        'path' => 'blog/draft-note.md',
                    ],
                ],
            ], JSON_PRETTY_PRINT)
        );

        $paths = new QueryIndexPaths($reader);
        $store = new QueryIndexSqliteStore($paths);
        $rebuilder = new QueryIndexRebuilder($this->contentIndex, $store);
        $this->assertSame(2, $rebuilder->rebuild());
        $this->sqliteIndex = new SqliteQueryIndex(
            $paths,
            new ContentStalenessService($settings),
            $rebuilder
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testDriverIdIsSqlite(): void
    {
        $this->assertSame(QueryIndexInterface::DRIVER_SQLITE, $this->sqliteIndex->driverId());
    }

    public function testQueryCountMatchesJsonDriver(): void
    {
        $pagination = new PaginationQuery(1, 10, '', '-updatedAt');
        $json = $this->jsonIndex->query('article', $pagination);
        $sqlite = $this->sqliteIndex->query('article', $pagination);
        $this->assertSame($json['total'], $sqlite['total']);
        $this->assertSame($json['entries'][0]->slug, $sqlite['entries'][0]->slug);
    }

    public function testFtsSearchFindsTitle(): void
    {
        $hits = $this->sqliteIndex->search('Hello', 'article', 10, false);
        $this->assertNotEmpty($hits);
        $this->assertSame('hello-world', $hits[0]->slug);
    }

    public function testSearchBindingDoesNotBroadenResults(): void
    {
        $hits = $this->sqliteIndex->search('"Hello" OR 1=1', 'article', 10, false);
        $this->assertCount(1, $hits);
        $this->assertSame('hello-world', $hits[0]->slug);
    }

    public function testPublishedFilterOnSearch(): void
    {
        $hits = $this->sqliteIndex->search('Draft', 'article', 10, true);
        $this->assertSame([], $hits);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
