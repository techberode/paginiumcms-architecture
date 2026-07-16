<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Services\BackupScheduler;
use PHPUnit\Framework\TestCase;

class BackupSchedulerTest extends TestCase
{
    public function testRunIfDueDelegatesToBackupManager(): void
    {
        $backup = $this->createMock(BackupInterface::class);
        $backup->expects($this->once())
            ->method('runScheduledBackupIfDue')
            ->willReturn(['ran' => false, 'reason' => 'not_due']);

        $scheduler = new BackupScheduler($backup);

        $this->assertSame(
            ['ran' => false, 'reason' => 'not_due'],
            $scheduler->runIfDue()
        );
    }
}
