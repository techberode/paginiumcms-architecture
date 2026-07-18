<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class BackupScheduledHandler implements JobHandlerInterface
{
    public function __construct(private BackupInterface $backups)
    {
    }

    public function key(): string
    {
        return 'backup.scheduled';
    }

    public function label(): string
    {
        return 'Scheduled backup';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $result = $this->backups->runScheduledBackupIfDue();

        return new JobRunResult(
            (bool) ($result['ran'] ?? false),
            (bool) ($result['ran'] ?? false) ? 'Backup created' : 'Backup not due',
            $result,
            isset($result['reason']) ? (string) $result['reason'] : null
        );
    }
}
