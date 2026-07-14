<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Health;

use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Health\Services\Checkers\SystemChecker;
use PaginiumCMS\Core\Health\Services\Checkers\StorageChecker;
use PaginiumCMS\Core\Health\Models\HealthStatus;
use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    private HealthCheckManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new HealthCheckManager();
    }

    public function testAddCheck(): void
    {
        $checker = new SystemChecker();
        $this->manager->addCheck($checker);

        $available = $this->manager->getAvailableChecks();
        $this->assertCount(1, $available);
        $this->assertEquals('system', $available[0]['name']);
    }

    public function testRunAllChecks(): void
    {
        $this->manager->addCheck(new SystemChecker());
        $this->manager->addCheck(new StorageChecker(__DIR__ . '/../../../storage'));

        $report = $this->manager->run();

        $this->assertNotEmpty($report->getId());
        $this->assertNotEmpty($report->getTimestamp());
        $this->assertIsArray($report->getChecks());
        $this->assertGreaterThanOrEqual(0, count($report->getChecks()));
    }

    public function testRunSingleCheck(): void
    {
        $this->manager->addCheck(new SystemChecker());

        $result = $this->manager->runCheck('system');
        $this->assertNotNull($result);
        $this->assertInstanceOf(HealthStatus::class, $result);
    }

    public function testRunNonExistentCheck(): void
    {
        $result = $this->manager->runCheck('non_existent');
        $this->assertNull($result);
    }

    public function testGetGroups(): void
    {
        $this->manager->addCheck(new SystemChecker());
        $this->manager->addCheck(new StorageChecker(__DIR__ . '/../../../storage'));

        $groups = $this->manager->getGroups();
        $this->assertArrayHasKey('system', $groups);
        $this->assertArrayHasKey('storage', $groups);
    }
}
