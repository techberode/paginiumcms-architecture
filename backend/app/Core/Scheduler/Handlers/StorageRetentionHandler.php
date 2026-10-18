<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\FlatFile\Services\ContentStorageRetentionService;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class StorageRetentionHandler implements JobHandlerInterface
{
    public function __construct(private ContentStorageRetentionService $retention)
    {
    }

    public function key(): string
    {
        return 'storage.retention';
    }

    public function label(): string
    {
        return 'Trash, version history, and draft orphan retention';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $stats = $this->retention->run();

        $message = sprintf(
            'Trash: %d removed (%s) · Versions: %d removed (%s) · Draft orphans: %d removed (%s)',
            $stats['trash_removed'],
            $stats['trash_enabled'] ? 'on' : 'off',
            $stats['versions_removed'],
            $stats['versions_enabled'] ? 'on' : 'off',
            $stats['draft_orphans_removed'],
            $stats['draft_prune_enabled'] ? 'on' : 'off'
        );

        return new JobRunResult(true, $message, $stats);
    }
}
