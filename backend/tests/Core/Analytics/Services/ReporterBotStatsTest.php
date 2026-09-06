<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Services\Reporter;
use PHPUnit\Framework\TestCase;

final class ReporterBotStatsTest extends TestCase
{
    public function testBotSummaryAndTopBots(): void
    {
        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->method('getVisits')->willReturn([
            [
                'visitorType' => 'human',
                'userAgent' => 'Mozilla/5.0 Chrome/120',
                'requestUri' => '/',
            ],
            [
                'visitorType' => 'bot',
                'botName' => 'Googlebot',
                'botKind' => 'search',
                'userAgent' => 'Googlebot/2.1',
                'requestUri' => '/blog',
            ],
            [
                'visitorType' => 'bot',
                'botName' => 'Googlebot',
                'botKind' => 'search',
                'userAgent' => 'Googlebot/2.1',
                'requestUri' => '/about',
            ],
        ]);

        $reporter = new Reporter($tracker);
        $summary = $reporter->getBotSummary('today');
        $topBots = $reporter->getTopBots(5, 'today');

        $this->assertSame(1, $summary['human']);
        $this->assertSame(2, $summary['bot']);
        $this->assertSame(66.7, $summary['bot_share']);
        $this->assertCount(1, $topBots);
        $this->assertSame('Googlebot', $topBots[0]['botName']);
        $this->assertSame(2, $topBots[0]['visits']);
    }
}
