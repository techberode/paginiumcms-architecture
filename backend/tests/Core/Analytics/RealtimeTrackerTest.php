<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics;

use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Services\RealtimeTracker;
use PHPUnit\Framework\TestCase;

final class RealtimeTrackerTest extends TestCase
{
    public function testSnapshotAggregatesVisitorsAndPages(): void
    {
        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->method('getRealtimeVisitors')->willReturn([
            [
                'visitorId' => 'v1',
                'requestUri' => '/home',
                'timestamp' => date('Y-m-d H:i:s'),
            ],
            [
                'visitorId' => 'v1',
                'requestUri' => '/about',
                'timestamp' => date('Y-m-d H:i:s'),
            ],
            [
                'visitorId' => 'v2',
                'requestUri' => '/home',
                'timestamp' => date('Y-m-d H:i:s'),
            ],
        ]);

        $snapshot = (new RealtimeTracker($tracker))->getSnapshot();

        $this->assertSame(300, $snapshot['window_seconds']);
        $this->assertSame(2, $snapshot['active_visitors']);
        $this->assertSame(3, $snapshot['active_page_views']);
        $this->assertSame('/home', $snapshot['top_active_pages'][0]['uri']);
        $this->assertSame(2, $snapshot['top_active_pages'][0]['views']);
    }

    public function testSnapshotReturnsEmptyWhenNoVisits(): void
    {
        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->method('getRealtimeVisitors')->willReturn([]);

        $snapshot = (new RealtimeTracker($tracker))->getSnapshot();

        $this->assertSame(0, $snapshot['active_visitors']);
        $this->assertSame([], $snapshot['top_active_pages']);
    }
}
