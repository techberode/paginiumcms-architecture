<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Services\Reporter;
use PHPUnit\Framework\TestCase;

final class ReporterTrendsTest extends TestCase
{
    public function testOverviewIncludesTrendsForMultiDayPeriod(): void
    {
        $today = date('Y-m-d');
        $eightDaysAgo = date('Y-m-d', strtotime('-8 days'));

        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->method('getDailyStats')->willReturn([
            'visits' => 0,
            'page_views' => 0,
        ]);
        $tracker->method('getRealtimeVisitors')->willReturn([]);
        $tracker->method('getVisits')->willReturnCallback(
            static function (string $date) use ($today, $eightDaysAgo): array {
                if ($date === $today) {
                    return [
                        ['visitorId' => 'a', 'duration' => 30],
                        ['visitorId' => 'b', 'duration' => 20],
                    ];
                }
                if ($date === $eightDaysAgo) {
                    return [
                        ['visitorId' => 'c', 'duration' => 10],
                    ];
                }

                return [];
            }
        );

        $reporter = new Reporter($tracker);
        $overview = $reporter->getOverview('7');

        $this->assertArrayHasKey('trends', $overview);
        $this->assertSame('up', $overview['trends']['page_views']['direction']);
        $this->assertSame(100.0, $overview['trends']['page_views']['percent']);
    }

    public function testPlatformStatsUsesStoredPlatformLabel(): void
    {
        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->method('getVisits')->willReturn([
            ['platformLabel' => 'PC Windows'],
            ['platformLabel' => 'Mobile'],
            ['platformLabel' => 'PC Windows'],
        ]);

        $reporter = new Reporter($tracker);
        $platforms = $reporter->getPlatformStats('today');

        $this->assertCount(2, $platforms);
        $this->assertSame('PC Windows', $platforms[0]['platform']);
        $this->assertSame(2, $platforms[0]['visits']);
    }
}
