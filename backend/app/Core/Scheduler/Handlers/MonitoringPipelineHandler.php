<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class MonitoringPipelineHandler implements JobHandlerInterface
{
    public function __construct(private MonitoringScheduler $monitoring)
    {
    }

    public function key(): string
    {
        return 'monitoring.pipeline';
    }

    public function label(): string
    {
        return 'Monitoring report + log scan';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $forceReport = (bool) ($payload['force_report'] ?? false);
        $report = $this->monitoring->runIfDue($forceReport);

        $sent = (bool) ($report['report']['sent'] ?? false);
        $notified = (int) ($report['logs']['notified'] ?? 0);

        return new JobRunResult(
            $sent || $notified > 0 || (($report['report']['reason'] ?? '') === 'not_due'),
            sprintf('Report: %s · Log notifications: %d', $sent ? 'sent' : 'skipped', $notified),
            $report
        );
    }
}
