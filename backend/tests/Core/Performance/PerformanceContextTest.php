<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\Performance\PerformanceContext;
use PHPUnit\Framework\TestCase;

final class PerformanceContextTest extends TestCase
{
    public function testRecordsStorageAndSessionTimings(): void
    {
        $context = new PerformanceContext();
        $context->reset();

        $context->recordStorageReadDuration(1_500_000);
        $context->recordStorageWriteDuration(500_000);
        $context->recordSessionLockDuration(2_000_000);
        $context->markSessionActive();

        $this->assertSame(1, $context->storageReads());
        $this->assertSame(1, $context->storageWrites());
        $this->assertSame(2.0, $context->storageMs());
        $this->assertSame(2.0, $context->sessionLockMs());
        $this->assertGreaterThanOrEqual(0.0, $context->sessionHeldMs());

        $context->recordSessionReleased();

        $this->assertGreaterThanOrEqual(0.0, $context->sessionHeldMs());
    }
}
