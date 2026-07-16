<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Models;

use PaginiumCMS\Modules\Audit\Contracts\AuditReportInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Model pre správu z auditu.
 */
class AuditReport implements AuditReportInterface
{
    private string $id;
    private string $timestamp;
    /** @var array<int|string, mixed> */
    private array $issues = [];
    private string $summary = '';

    public function __construct()
    {
        $this->id = uniqid('audit_', true);
        $this->timestamp = date('Y-m-d H:i:s');
    }

    public function addIssue(AuditIssue $issue): self
    {
        $this->issues[] = $issue;
        return $this;
    }

    /**
     * @param array<int|string, mixed> $issues
     */
    public function addIssues(array $issues): self
    {
        foreach ($issues as $issue) {
            if ($issue instanceof AuditIssue) {
                $this->issues[] = $issue;
            }
        }
        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getIssuesBySeverity(string $severity): array
    {
        return array_filter($this->issues, function (AuditIssue $issue) use ($severity) {
            return $issue->getSeverity() === $severity;
        });
    }

    public function getTotalIssues(): int
    {
        return count($this->issues);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getSeverityCounts(): array
    {
        $counts = [];
        foreach (AuditSeverity::getAll() as $severity) {
            $counts[$severity] = count($this->getIssuesBySeverity($severity));
        }
        return $counts;
    }

    public function isPassed(): bool
    {
        // Audit prejde, ak neexistujú kritické alebo chybové problémy
        $criticalCount = count($this->getIssuesBySeverity(AuditSeverity::CRITICAL));
        $errorCount = count($this->getIssuesBySeverity(AuditSeverity::ERROR));
        return $criticalCount === 0 && $errorCount === 0;
    }

    public function getSummary(): string
    {
        if (empty($this->summary)) {
            $this->generateSummary();
        }
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    private function generateSummary(): void
    {
        $counts = $this->getSeverityCounts();
        $total = $this->getTotalIssues();

        if ($total === 0) {
            $this->summary = '✅ Audit prebehol bez problémov. Všetky kontroly sú v poriadku.';
            return;
        }

        $parts = [];
        if ($counts[AuditSeverity::CRITICAL] > 0) {
            $parts[] = $counts[AuditSeverity::CRITICAL] . ' kritických';
        }
        if ($counts[AuditSeverity::ERROR] > 0) {
            $parts[] = $counts[AuditSeverity::ERROR] . ' chýb';
        }
        if ($counts[AuditSeverity::WARNING] > 0) {
            $parts[] = $counts[AuditSeverity::WARNING] . ' varovaní';
        }
        if ($counts[AuditSeverity::INFO] > 0) {
            $parts[] = $counts[AuditSeverity::INFO] . ' informácií';
        }

        $status = $this->isPassed() ? '⚠️' : '❌';
        $this->summary = sprintf(
            '%s Audit našiel %s problémov: %s.',
            $status,
            $total,
            implode(', ', $parts)
        );
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp,
            'summary' => $this->getSummary(),
            'passed' => $this->isPassed(),
            'total_issues' => $this->getTotalIssues(),
            'severity_counts' => $this->getSeverityCounts(),
            'issues' => array_map(function (AuditIssue $issue) {
                return $issue->jsonSerialize();
            }, $this->issues),
        ];
    }

    public function toJson(): string
    {
        return JsonHelper::encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
