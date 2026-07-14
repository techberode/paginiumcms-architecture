<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Services;

use PaginiumCMS\Modules\Audit\Contracts\AuditEngineInterface;
use PaginiumCMS\Modules\Audit\Contracts\AuditorInterface;
use PaginiumCMS\Modules\Audit\Models\AuditReport;

/**
 * Hlavný engine pre Audit.
 */
class AuditEngine implements AuditEngineInterface
{
    /** @var array<int, AuditorInterface> */
    private array $auditors = [];

    public function __construct(array $auditors = [])
    {
        foreach ($auditors as $auditor) {
            $this->addAuditor($auditor);
        }
    }

    public function run(array $options = []): AuditReport
    {
        $report = new AuditReport();

        foreach ($this->auditors as $auditor) {
            $issues = $auditor->run($options);
            $report->addIssues($issues);
        }

        return $report;
    }

    public function runSelected(array $auditors): AuditReport
    {
        $report = new AuditReport();

        foreach ($this->auditors as $auditor) {
            if (in_array($auditor->getName(), $auditors, true)) {
                $issues = $auditor->run();
                $report->addIssues($issues);
            }
        }

        return $report;
    }

    public function getAvailableAuditors(): array
    {
        return array_map(function (AuditorInterface $auditor) {
            return $auditor->getName();
        }, $this->auditors);
    }

    public function addAuditor(AuditorInterface $auditor): void
    {
        $this->auditors[] = $auditor;
    }
}
