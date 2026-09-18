<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\HybridEngine\QueryIndex\FallbackQueryIndex;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexCapabilityProbe;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexFailureHandler;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexFactory;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRuntimeWatch;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexPaths;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRebuilder;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexSqliteStore;
use PaginiumCMS\Core\HybridEngine\QueryIndex\SqliteQueryIndex;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class QueryIndexFactoryTest extends TestCase
{
    public function testSqliteSettingUsesFallbackWrapperWhenRuntimeReady(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available.');
        }

        $baseDir = sys_get_temp_dir() . '/pag_qfact_' . uniqid('', true);
        mkdir($baseDir . '/data/index', 0777, true);
        $validator = new FileValidator($baseDir);
        $reader = new FileReader($validator);
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $settingsRepo->method('get')->willReturnCallback(static function (string $key) {
            if ($key === 'engine.queryIndexDriver') {
                return QueryIndexInterface::DRIVER_SQLITE;
            }

            return 'sk';
        });
        $settingsRepo->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'engine') {
                return [
                    'queryIndexDriver' => QueryIndexInterface::DRIVER_SQLITE,
                    'queryIndexRuntimeWatchEnabled' => false,
                ];
            }

            return [];
        });

        $contentIndex = new ContentIndexService(
            $reader,
            new LocalizedContentNormalizer($settingsRepo),
            new ContentStalenessService($settingsRepo),
            'data/index/content.json'
        );

        file_put_contents(
            $baseDir . '/data/index/content.json',
            JsonHelper::encode(['version' => 1, 'items' => []], JSON_PRETTY_PRINT)
        );

        $paths = new QueryIndexPaths($reader);
        $store = new QueryIndexSqliteStore($paths);
        $rebuilder = new QueryIndexRebuilder($contentIndex, $store);
        $rebuilder->rebuild();
        $probe = new QueryIndexCapabilityProbe($paths, $rebuilder, $store, $contentIndex);
        $sqlite = new SqliteQueryIndex($paths, new ContentStalenessService($settingsRepo), $rebuilder);

        $watch = new QueryIndexRuntimeWatch($settingsRepo, $probe, $paths, $store);
        $failureHandler = new QueryIndexFailureHandler(
            $settingsRepo,
            $watch,
            IncidentNotifierTestFactory::create($settingsRepo, new NotificationService()),
            new CacheManager(new MemoryDriver(), 'test_qfact_'),
            new SecurityLogger($this->createMock(LoggerInterface::class))
        );
        $factory = new QueryIndexFactory($contentIndex, $settingsRepo, $probe, $sqlite, $failureHandler);
        $index = $factory->create();

        $this->assertInstanceOf(FallbackQueryIndex::class, $index);
        $this->assertSame(QueryIndexInterface::DRIVER_SQLITE, $index->driverId());

        $this->removeDir($baseDir);
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
