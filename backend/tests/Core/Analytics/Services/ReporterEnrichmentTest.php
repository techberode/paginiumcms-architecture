<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Services\Reporter;
use PHPUnit\Framework\TestCase;

final class ReporterEnrichmentTest extends TestCase
{
    public function testTopReferersIncludeSourceMetadata(): void
    {
        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->method('getVisits')->willReturn([
            ['referer' => 'https://www.google.com/search?q=test'],
            ['referer' => 'https://www.google.com/search?q=other'],
            ['referer' => ''],
        ]);

        $reporter = new Reporter($tracker);
        $top = $reporter->getTopReferers(5, 'today');

        $this->assertCount(2, $top);
        $this->assertSame('Google', $top[0]['source']);
        $this->assertSame('search', $top[0]['type']);
        $this->assertSame(2, $top[0]['visits']);
        $this->assertSame('direct', $top[1]['type']);
    }

    public function testGeoStatsIncludeCountryCodeAndMaskedIps(): void
    {
        $tracker = $this->createMock(TrackerInterface::class);
        $tracker->method('getVisits')->willReturn([
            [
                'country' => 'Slovakia',
                'countryCode' => 'SK',
                'city' => 'Bratislava',
                'ip' => '203.0.113.10',
            ],
            [
                'country' => 'Slovakia',
                'countryCode' => 'SK',
                'city' => 'Košice',
                'ip' => '203.0.113.11',
            ],
        ]);

        $reporter = new Reporter($tracker);
        $geo = $reporter->getGeoStats('today');

        $this->assertCount(1, $geo);
        $this->assertSame('SK', $geo[0]['countryCode']);
        $this->assertSame(2, $geo[0]['visits']);
        $this->assertContains('203.0.xxx.xxx', $geo[0]['sample_ips']);
    }
}
