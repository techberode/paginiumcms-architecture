<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Audit\Services;

use PaginiumCMS\Modules\Audit\Services\AuditEngine;
use PaginiumCMS\Modules\Audit\Services\SecurityAuditor;
use PHPUnit\Framework\TestCase;

class AuditEngineTest extends TestCase
{
    private AuditEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $auditor = new SecurityAuditor(__DIR__ . '/../../../..');
        $this->engine = new AuditEngine([$auditor]);
    }

    public function testRun(): void
    {
        $report = $this->engine->run();

        $this->assertNotEmpty($report->getId());
        $this->assertGreaterThanOrEqual(0, $report->getTotalIssues());
    }

    public function testGetAvailableAuditors(): void
    {
        $auditors = $this->engine->getAvailableAuditors();

        $this->assertContains('security', $auditors);
    }

    public function testRunSelected(): void
    {
        $report = $this->engine->runSelected(['security']);

        $this->assertNotEmpty($report->getId());
    }
}
