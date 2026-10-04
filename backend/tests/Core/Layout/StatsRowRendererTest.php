<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\StatsRowRenderer;
use PHPUnit\Framework\TestCase;

final class StatsRowRendererTest extends TestCase
{
    public function testStaticRowWithoutAnimation(): void
    {
        $html = StatsRowRenderer::render([], '<div class="pg-stat">x</div>');
        $this->assertStringContainsString('pg-stats pg-reveal', $html);
        $this->assertStringNotContainsString('pg-island', $html);
    }

    public function testCountUpUsesStatsRowIslandShell(): void
    {
        $html = StatsRowRenderer::render(['animate' => 'count-up'], '<div class="pg-stat">x</div>');
        $this->assertStringContainsString('pg-island--stats-row', $html);
        $this->assertStringContainsString('data-animate="count-up"', $html);
    }
}
