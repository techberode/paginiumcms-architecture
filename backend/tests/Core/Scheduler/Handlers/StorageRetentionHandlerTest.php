<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentStorageRetentionService;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Core\Scheduler\Handlers\StorageRetentionHandler;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PHPUnit\Framework\TestCase;

final class StorageRetentionHandlerTest extends TestCase
{
    public function testHandleReturnsSummary(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('storageRetention')->willReturn([
            'trashAutoPurgeEnabled' => true,
            'trashRetentionDays' => 1,
            'contentVersionRetentionEnabled' => true,
            'contentVersionRetentionDays' => 30,
            'draftOrphanPruneEnabled' => true,
        ]);

        $trash = $this->createMock(TrashService::class);
        $trash->method('purgeOlderThan')->willReturn(2);

        $versions = $this->createMock(EnhancedVersionManager::class);
        $versions->method('purgeVersionsOlderThan')->willReturn(5);

        $drafts = $this->createMock(DraftManagerInterface::class);
        $drafts->method('pruneUnsavedNewOrphansKeepLatest')->willReturn(1);

        $service = new ContentStorageRetentionService($settings, $trash, $versions, $drafts);
        $handler = new StorageRetentionHandler($service);
        $result = $handler->handle();

        $this->assertTrue($result->success);
        $this->assertSame('storage.retention', $handler->key());
        $this->assertStringContainsString('Trash: 2 removed', $result->message);
    }
}
