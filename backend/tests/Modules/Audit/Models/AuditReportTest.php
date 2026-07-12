<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Audit\Models;

use PaginiumCMS\Modules\Audit\Models\AuditReport;
use PaginiumCMS\Modules\Audit\Models\AuditIssue;
use PaginiumCMS\Modules\Audit\Models\AuditSeverity;
use PHPUnit\Framework\TestCase;

class AuditReportTest extends TestCase
{
    public function testCreateReport(): void
    {
        $report = new AuditReport();

        $this->assertNotEmpty($report->getId());
        $this->assertNotEmpty($report->getTimestamp());
        $this->assertEquals(0, $report->getTotalIssues());
        $this->assertTrue($report->isPassed());
    }

    public function testAddIssue(): void
{
    $report = new AuditReport();
    $issue = new AuditIssue(
        AuditSeverity::WARNING,
        'test',
        'Test issue',
        'Test description'
    );

    $report->addIssue($issue);

    $this->assertEquals(1, $report->getTotalIssues());
    // WARNING neovplyvňuje isPassed() – stále vráti true
    $this->assertTrue($report->isPassed());
}

public function testAddCriticalIssue(): void
{
    $report = new AuditReport();
    $issue = new AuditIssue(
        AuditSeverity::CRITICAL,
        'test',
        'Critical issue',
        'Test description'
    );

    $report->addIssue($issue);

    $this->assertEquals(1, $report->getTotalIssues());
    $this->assertFalse($report->isPassed());
}

    public function testGetIssuesBySeverity(): void
    {
        $report = new AuditReport();
        $report->addIssue(new AuditIssue(AuditSeverity::CRITICAL, 'test', 'Critical', 'Desc'));
        $report->addIssue(new AuditIssue(AuditSeverity::ERROR, 'test', 'Error', 'Desc'));
        $report->addIssue(new AuditIssue(AuditSeverity::WARNING, 'test', 'Warning', 'Desc'));
        $report->addIssue(new AuditIssue(AuditSeverity::INFO, 'test', 'Info', 'Desc'));

        $this->assertCount(1, $report->getIssuesBySeverity(AuditSeverity::CRITICAL));
        $this->assertCount(1, $report->getIssuesBySeverity(AuditSeverity::ERROR));
        $this->assertCount(1, $report->getIssuesBySeverity(AuditSeverity::WARNING));
        $this->assertCount(1, $report->getIssuesBySeverity(AuditSeverity::INFO));
    }

    public function testSeverityCounts(): void
    {
        $report = new AuditReport();
        $report->addIssue(new AuditIssue(AuditSeverity::CRITICAL, 'test', 'Critical', 'Desc'));
        $report->addIssue(new AuditIssue(AuditSeverity::ERROR, 'test', 'Error', 'Desc'));

        $counts = $report->getSeverityCounts();

        $this->assertEquals(1, $counts[AuditSeverity::CRITICAL]);
        $this->assertEquals(1, $counts[AuditSeverity::ERROR]);
        $this->assertEquals(0, $counts[AuditSeverity::WARNING]);
        $this->assertEquals(0, $counts[AuditSeverity::INFO]);
    }

    public function testToArray(): void
    {
        $report = new AuditReport();
        $report->addIssue(new AuditIssue(
            AuditSeverity::WARNING,
            'test',
            'Test issue',
            'Test description'
        )->setRecommendation('Test recommendation'));

        $data = $report->toArray();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('passed', $data);
        $this->assertArrayHasKey('total_issues', $data);
        $this->assertArrayHasKey('severity_counts', $data);
        $this->assertArrayHasKey('issues', $data);
        $this->assertCount(1, $data['issues']);
    }

    public function testToJson(): void
    {
        $report = new AuditReport();
        $json = $report->toJson();

        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertArrayHasKey('id', $data);
    }
}
