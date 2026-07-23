<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Services;

use PaginiumCMS\Core\Scheduler\Services\CronExpressionEvaluator;
use PHPUnit\Framework\TestCase;

final class CronExpressionEvaluatorTest extends TestCase
{
    private CronExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new CronExpressionEvaluator();
    }

    public function testEveryMinuteMatchesAtMinuteBoundary(): void
    {
        $at = new \DateTimeImmutable('2026-07-18 10:15:00');
        $this->assertTrue($this->evaluator->isDue('* * * * *', $at));
    }

    public function testDailyAtTwoAm(): void
    {
        $due = new \DateTimeImmutable('2026-07-18 02:00:00');
        $notDue = new \DateTimeImmutable('2026-07-18 03:00:00');
        $this->assertTrue($this->evaluator->isDue('0 2 * * *', $due));
        $this->assertFalse($this->evaluator->isDue('0 2 * * *', $notDue));
    }

    public function testIsDueSinceLastRunSkipsSameMinute(): void
    {
        $timezone = new \DateTimeZone('UTC');
        $at = new \DateTimeImmutable('2026-07-18 10:15:30', $timezone);
        $lastRun = '2026-07-18T10:15:00+00:00';
        $this->assertFalse($this->evaluator->isDueSinceLastRun('* * * * *', $lastRun, $at));
    }

    public function testIsDueSinceLastRunRunsOnNextMinute(): void
    {
        $timezone = new \DateTimeZone('UTC');
        $at = new \DateTimeImmutable('2026-07-18 10:16:00', $timezone);
        $lastRun = '2026-07-18T10:15:00+00:00';
        $this->assertTrue($this->evaluator->isDueSinceLastRun('* * * * *', $lastRun, $at));
    }

    public function testDescribeNextRunReturnsFutureTimestamp(): void
    {
        $from = new \DateTimeImmutable('2026-07-18 01:59:00');
        $next = $this->evaluator->describeNextRun('0 2 * * *', $from);
        $this->assertSame('2026-07-18 02:00:00', $next);
    }
}
