<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Analytics\Services\AnalyticsRetentionService;
use PaginiumCMS\Core\Logging\Services\LogRetentionService;
use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;

final class MaintenanceCleanupHandler implements JobHandlerInterface
{
    public function __construct(
        private AnalyticsRetentionService $analyticsRetention,
        private LogRetentionService $logRetention
    ) {
    }

    public function key(): string
    {
        return 'maintenance.cleanup';
    }

    public function label(): string
    {
        return 'Analytics + log retention purge';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $analyticsDays = isset($payload['analytics_days']) ? (int) $payload['analytics_days'] : null;
        $logDays = isset($payload['log_days']) ? (int) $payload['log_days'] : null;

        $analytics = $this->analyticsRetention->purgeOldData($analyticsDays);
        $logs = $this->logRetention->purgeOldLogs($logDays);

        $summary = sprintf(
            'Analytics: %d visit + %d daily + %d visitor files · Logs: %d app + %d audit + %d event + %d user files',
            $analytics['visits'],
            $analytics['daily'],
            $analytics['visitors'],
            $logs['app'],
            $logs['audit'],
            $logs['event'],
            $logs['user']
        );

        return new JobRunResult(true, $summary, [
            'analytics' => $analytics,
            'logs' => $logs,
        ]);
    }
}
