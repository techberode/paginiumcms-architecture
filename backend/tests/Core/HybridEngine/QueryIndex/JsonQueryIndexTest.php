<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\HybridEngine\QueryIndex\JsonQueryIndex;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexCapabilityProbe;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexFailureHandler;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexFactory;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRuntimeWatch;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexPaths;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRebuilder;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexSqliteStore;
use PaginiumCMS\Core\HybridEngine\QueryIndex\SqliteQueryIndex;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\PaginationQuery;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;

final class JsonQueryIndexTest extends TestCase
{
    private string $baseDir;

    private ContentIndexService $contentIndex;

    private JsonQueryIndex $queryIndex;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_qidx_' . uniqid('', true);
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
        $this->queryIndex = new JsonQueryIndex($this->contentIndex);

        file_put_contents(
            $this->baseDir . '/data/index/content.json',
            JsonHelper::encode([
                'version' => 1,
                'items' => [
                    [
                        'type' => 'article',
                        'slug' => 'hello',
                        'title' => 'Hello World',
                        'excerpt' => '',
                        'tags' => ['news'],
                        'category' => 'blog',
                        'author' => 'Admin',
                        'status' => 'published',
                        'locale' => 'sk',
                        'createdAt' => '2026-01-01',
                        'updatedAt' => '2026-01-02',
                        'path' => 'blog/hello.md',
                    ],
                ],
            ], JSON_PRETTY_PRINT)
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testDriverIdIsJson(): void
    {
        $this->assertSame(QueryIndexInterface::DRIVER_JSON, $this->queryIndex->driverId());
    }

    public function testQueryDelegatesToContentIndex(): void
    {
        $result = $this->queryIndex->query('article', new PaginationQuery(1, 10, '', '-updatedAt'));
        $this->assertSame(1, $result['total']);
        $this->assertSame('hello', $result['entries'][0]->slug);
    }

    public function testFactoryDefaultsToJsonDriver(): void
    {
        $settings = new SettingsRepository(
            new \PaginiumCMS\Core\FlatFile\Services\FileWriter(new FileValidator($this->baseDir)),
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );
        $paths = new QueryIndexPaths(new FileReader(new FileValidator($this->baseDir)));
        $store = new QueryIndexSqliteStore($paths);
        $rebuilder = new QueryIndexRebuilder($this->contentIndex, $store);
        $probe = new QueryIndexCapabilityProbe($paths, $rebuilder, $store, $this->contentIndex);
        $sqlite = new SqliteQueryIndex(
            $paths,
            new ContentStalenessService($settings),
            $rebuilder
        );
        $watch = new QueryIndexRuntimeWatch($settings, $probe, $paths, $store);
        $failureHandler = new QueryIndexFailureHandler(
            $settings,
            $watch,
            IncidentNotifierTestFactory::create($settings, new NotificationService()),
            new CacheManager(new MemoryDriver(), 'test_qidx_'),
            new SecurityLogger($this->createMock(LoggerInterface::class))
        );
        $factory = new QueryIndexFactory($this->contentIndex, $settings, $probe, $sqlite, $failureHandler);
        $index = $factory->create();
        $this->assertSame(QueryIndexInterface::DRIVER_JSON, $index->driverId());
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
