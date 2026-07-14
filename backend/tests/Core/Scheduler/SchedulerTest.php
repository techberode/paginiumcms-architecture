<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler;

use PaginiumCMS\Core\Scheduler\Scheduler;
use PaginiumCMS\Core\Scheduler\Job;
use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{
    private Scheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new Scheduler();
    }

    public function testAddJob(): void
    {
        $job = new Job('test_job', function () {});
        $this->scheduler->addJob($job);

        $jobs = $this->scheduler->getJobs();
        $this->assertCount(1, $jobs);
        $this->assertEquals('test_job', $jobs[0]->getName());
    }

    public function testJobExecution(): void
    {
        $executed = false;
        $job = new Job('test_job', function () use (&$executed) {
            $executed = true;
        }, '* * * * *');

        $this->scheduler->addJob($job);
        $this->scheduler->run();

        $this->assertTrue($executed);
    }

    public function testJobNotDue(): void
    {
        $executed = false;
        $job = new Job('test_job', function () use (&$executed) {
            $executed = true;
        }, '0 0 31 2 *'); // 31. február neexistuje

        $this->scheduler->addJob($job);
        $this->scheduler->run();

        $this->assertFalse($executed);
    }
}
