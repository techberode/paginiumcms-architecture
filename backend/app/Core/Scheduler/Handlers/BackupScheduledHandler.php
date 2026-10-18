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
        $ran = (bool) $result['ran'];

        $message = 'Backup not due';
        if ($ran) {
            $offsite = $result['offsiteUploaded'] ?? null;
            if ($offsite === true) {
                $message = 'Backup created; off-site upload OK';
            } elseif ($offsite === false) {
                $message = 'Backup created; off-site upload failed — check PHP error log';
            } else {
                $message = 'Backup created';
            }
        }

        return new JobRunResult(
            $ran,
            $message,
            $result,
            isset($result['reason']) ? (string) $result['reason'] : null
        );
    }
}
