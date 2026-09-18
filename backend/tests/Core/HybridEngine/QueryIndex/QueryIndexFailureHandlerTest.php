<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexCapabilityProbe;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexFailureHandler;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexPaths;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRebuilder;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRuntimeWatch;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexSqliteStore;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PHPUnit\Framework\TestCase;

final class QueryIndexFailureHandlerTest extends TestCase
{
    public function testThrottlesRepeatedAlertsForSameIssue(): void
    {
        $baseDir = sys_get_temp_dir() . '/pag_qi_hdl_' . uniqid('', true);
        mkdir($baseDir . '/data/index', 0777, true);
        file_put_contents(
            $baseDir . '/data/index/content.json',
            JsonHelper::encode(['version' => 1, 'items' => []], JSON_PRETTY_PRINT)
        );

        $settings = $this->settingsForWatch(true);
        $handler = $this->makeHandler($baseDir, $settings, $this->adapterExpectingSendCount(1));

        $handler->handleDetectedIssue();
        $handler->handleDetectedIssue();

        $this->removeDir($baseDir);
    }

    public function testAutoFallbackUpdatesDriverAndNotifies(): void
    {
        $baseDir = sys_get_temp_dir() . '/pag_qi_hdl_fb_' . uniqid('', true);
        mkdir($baseDir . '/data/index', 0777, true);
        file_put_contents(
            $baseDir . '/data/index/content.json',
            JsonHelper::encode(['version' => 1, 'items' => []], JSON_PRETTY_PRINT)
        );

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'engine') {
                return [
                    'queryIndexDriver' => QueryIndexInterface::DRIVER_SQLITE,
                    'queryIndexRuntimeWatchEnabled' => true,
                    'queryIndexFailureAlertCooldownSeconds' => 60,
                    'queryIndexAutoFallbackOnFailure' => true,
                ];
            }
            if ($group === 'monitoring') {
                return ['alertsEnabled' => true, 'minSeverity' => 'info', 'alertEmail' => 'ops@example.com'];
            }

            return [];
        });
        $settings->expects($this->once())
            ->method('setGroup')
            ->with('engine', ['queryIndexDriver' => QueryIndexInterface::DRIVER_JSON]);

        $handler = $this->makeHandler(
            $baseDir,
            $settings,
            $this->adapterExpectingSendCount(2)
        );
        $handler->handleDetectedIssue(QueryIndexRuntimeWatch::ISSUE_INTEGRITY_FAILED);

        $this->removeDir($baseDir);
    }

    public function testSkipsWhenWatchInactive(): void
    {
        $baseDir = sys_get_temp_dir() . '/pag_qi_hdl_off_' . uniqid('', true);
        mkdir($baseDir . '/data/index', 0777, true);

        $settings = $this->settingsForWatch(false);
        $handler = $this->makeHandler($baseDir, $settings, $this->adapterExpectingSendCount(0));

        $handler->handleDetectedIssue();
        $handler->handleQueryFallback();

        $this->removeDir($baseDir);
    }

    private function makeHandler(string $baseDir, SettingsRepositoryInterface $settings, AdapterInterface $adapter): QueryIndexFailureHandler
    {
        $validator = new FileValidator($baseDir);
        $reader = new FileReader($validator);
        $contentIndex = new ContentIndexService(
            $reader,
            new \PaginiumCMS\Core\Content\LocalizedContentNormalizer($settings),
            new ContentStalenessService($settings),
            'data/index/content.json'
        );
        $paths = new QueryIndexPaths($reader);
        $store = new QueryIndexSqliteStore($paths);
        $rebuilder = new QueryIndexRebuilder($contentIndex, $store);
        $probe = new QueryIndexCapabilityProbe($paths, $store, $contentIndex);
        $watch = new QueryIndexRuntimeWatch($settings, $probe, $paths, $store);

        $notifications = new NotificationService();
        $notifications->addAdapter('email', $adapter);

        return new QueryIndexFailureHandler(
            $settings,
            $watch,
            IncidentNotifierTestFactory::create($settings, $notifications),
            new CacheManager(new MemoryDriver(), 'test_qi_'),
            new SecurityLogger($this->createMock(LoggerInterface::class))
        );
    }

    private function settingsForWatch(bool $active): SettingsRepositoryInterface
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('sk');
        $settings->method('group')->willReturnCallback(static function (string $group) use ($active): array {
            if ($group === 'engine') {
                return [
                    'queryIndexDriver' => $active ? QueryIndexInterface::DRIVER_SQLITE : QueryIndexInterface::DRIVER_JSON,
                    'queryIndexRuntimeWatchEnabled' => true,
                    'queryIndexFailureAlertCooldownSeconds' => 900,
                    'queryIndexAutoFallbackOnFailure' => false,
                ];
            }
            if ($group === 'monitoring') {
                return ['alertsEnabled' => true, 'minSeverity' => 'warning', 'alertEmail' => 'ops@example.com'];
            }

            return [];
        });

        return $settings;
    }

    private function adapterExpectingSendCount(int $count): AdapterInterface
    {
        $adapter = $this->createMock(AdapterInterface::class);
        if ($count === 0) {
            $adapter->expects($this->never())->method('send');
        } else {
            $adapter->expects($this->exactly($count))->method('send');
        }

        return $adapter;
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
