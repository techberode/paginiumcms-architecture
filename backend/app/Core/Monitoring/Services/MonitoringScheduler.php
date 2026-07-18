<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Monitoring\Services;

/**
 * Orchestrates scheduled monitoring jobs (Iteration 7).
 */
final class MonitoringScheduler
{
    public function __construct(
        private MonitoringReportScheduler $reports,
        private LogIncidentScanner $logScanner
    ) {
    }

    /**
     * @return array{report: array<string, mixed>, logs: array<string, mixed>}
     */
    public function runIfDue(): array
    {
        return [
            'report' => $this->reports->runIfDue(),
            'logs' => $this->logScanner->scan(),
        ];
    }
}
