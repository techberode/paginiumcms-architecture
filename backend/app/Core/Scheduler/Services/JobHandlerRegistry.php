<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Handlers\BackupScheduledHandler;
use PaginiumCMS\Core\Scheduler\Handlers\ContentScheduledPublishHandler;
use PaginiumCMS\Core\Scheduler\Handlers\MonitoringPipelineHandler;

/**
 * Maps handler keys to DI-resolved handlers (Iteration 29).
 */
final class JobHandlerRegistry
{
    public function __construct(
        private BackupScheduledHandler $backup,
        private MonitoringPipelineHandler $monitoring,
        private ContentScheduledPublishHandler $scheduledPublish
    ) {
    }

    public function get(string $key): ?JobHandlerInterface
    {
        return match ($key) {
            'backup.scheduled' => $this->backup,
            'monitoring.pipeline' => $this->monitoring,
            'content.scheduled_publish' => $this->scheduledPublish,
            default => null,
        };
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function catalog(): array
    {
        return [
            ['key' => $this->backup->key(), 'label' => $this->backup->label()],
            ['key' => $this->monitoring->key(), 'label' => $this->monitoring->label()],
            ['key' => $this->scheduledPublish->key(), 'label' => $this->scheduledPublish->label()],
        ];
    }
}
