<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\AppTimezone;
use PHPUnit\Framework\TestCase;

final class AppTimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        AppTimezone::apply('UTC');
        parent::tearDown();
    }

    public function testApplySetsValidTimezone(): void
    {
        $this->assertTrue(AppTimezone::apply('Europe/Bratislava'));
        $this->assertSame('Europe/Bratislava', AppTimezone::current());
    }

    public function testApplyRejectsInvalidTimezone(): void
    {
        $before = AppTimezone::current();
        $this->assertFalse(AppTimezone::apply('Not/A/Timezone'));
        $this->assertSame($before, AppTimezone::current());
    }

    public function testNowUsesActiveTimezone(): void
    {
        AppTimezone::apply('Europe/Bratislava');
        $local = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Bratislava'));

        $this->assertSame($local->format('Y-m-d'), substr(AppTimezone::now(), 0, 10));
    }

    public function testApplyWithDstDisabledUsesFixedStandardOffset(): void
    {
        AppTimezone::applyWithDst('Europe/Bratislava', false);

        $resolved = AppTimezone::current();
        $zone = new \DateTimeZone($resolved);
        $january = new \DateTimeImmutable('2026-01-15 12:00:00', $zone);
        $july = new \DateTimeImmutable('2026-07-15 12:00:00', $zone);

        $this->assertSame($zone->getOffset($january), $zone->getOffset($july));
        $this->assertSame(3600, $zone->getOffset($january));
    }

    public function testIsDaylightSavingActiveForBratislavaInJuly(): void
    {
        $summer = new \DateTimeImmutable('2026-07-15 12:00:00', new \DateTimeZone('Europe/Bratislava'));
        $this->assertTrue(AppTimezone::isDaylightSavingActive('Europe/Bratislava', $summer));
    }
}
