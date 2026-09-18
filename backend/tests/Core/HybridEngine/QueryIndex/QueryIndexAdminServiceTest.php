<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexAdminService;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexCapabilityProbe;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexInterface;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexPaths;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRebuilder;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexSqliteStore;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class QueryIndexAdminServiceTest extends TestCase
{
    public function testActivateSqliteRejectedWhenProbeFails(): void
    {
        $baseDir = sys_get_temp_dir() . '/pag_qadm_' . uniqid('', true);
        mkdir($baseDir . '/data/index', 0777, true);
        $validator = new FileValidator($baseDir);
        $reader = new FileReader($validator);
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $settingsRepo->method('get')->willReturn('sk');
        $settingsRepo->method('group')->willReturn(['queryIndexDriver' => 'json']);

        $contentIndex = new ContentIndexService(
            $reader,
            new LocalizedContentNormalizer($settingsRepo),
            new ContentStalenessService($settingsRepo),
            'data/index/content.json'
        );
        $paths = new QueryIndexPaths($reader);
        $store = new QueryIndexSqliteStore($paths);
        $rebuilder = new QueryIndexRebuilder($contentIndex, $store);
        $probe = new QueryIndexCapabilityProbe($paths, $store, $contentIndex);

        $service = new QueryIndexAdminService($settingsRepo, $probe, $rebuilder, $contentIndex);

        $this->expectException(InvalidArgumentException::class);
        $service->activateDriver(QueryIndexInterface::DRIVER_SQLITE);

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
