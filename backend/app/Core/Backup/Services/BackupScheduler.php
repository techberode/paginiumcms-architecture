<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;

/**
 * Tenká vrstva pre cron/CLI – deleguje na BackupManager.
 */
class BackupScheduler
{
    public function __construct(
        private BackupInterface $backupManager
    ) {
    }

    /**
     * @return array{ran: bool, reason?: string, backup?: mixed}
     */
    public function runIfDue(): array
    {
        return $this->backupManager->runScheduledBackupIfDue();
    }
}
