<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Handlers\BackupScheduledHandler;
use PaginiumCMS\Core\Scheduler\Handlers\ContentScheduledPublishHandler;
use PaginiumCMS\Core\Scheduler\Handlers\GitPublishHandler;
use PaginiumCMS\Core\Scheduler\Handlers\MonitoringPipelineHandler;
use PaginiumCMS\Core\Scheduler\Handlers\SystemDeployHandler;
use PaginiumCMS\Core\Scheduler\Handlers\WebhookDeliveryHandler;
use PaginiumCMS\Modules\Newsletter\Handlers\NewsletterWeeklyDigestHandler;

/**
 * Maps handler keys to DI-resolved handlers (Iteration 29).
 */
final class JobHandlerRegistry
{
    public function __construct(
        private BackupScheduledHandler $backup,
        private MonitoringPipelineHandler $monitoring,
        private ContentScheduledPublishHandler $scheduledPublish,
        private SystemDeployHandler $systemDeploy,
        private NewsletterWeeklyDigestHandler $newsletterWeeklyDigest,
        private GitPublishHandler $gitPublish,
        private WebhookDeliveryHandler $webhookDeliver,
    ) {
    }

    public function get(string $key): ?JobHandlerInterface
    {
        return match ($key) {
            'backup.scheduled' => $this->backup,
            'monitoring.pipeline' => $this->monitoring,
            'content.scheduled_publish' => $this->scheduledPublish,
            'system.deploy' => $this->systemDeploy,
            'newsletter.weekly_digest' => $this->newsletterWeeklyDigest,
            'git.publish' => $this->gitPublish,
            'webhook.deliver' => $this->webhookDeliver,
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
            ['key' => $this->systemDeploy->key(), 'label' => $this->systemDeploy->label()],
            ['key' => $this->newsletterWeeklyDigest->key(), 'label' => $this->newsletterWeeklyDigest->label()],
            ['key' => $this->gitPublish->key(), 'label' => $this->gitPublish->label()],
            ['key' => $this->webhookDeliver->key(), 'label' => $this->webhookDeliver->label()],
        ];
    }
}
