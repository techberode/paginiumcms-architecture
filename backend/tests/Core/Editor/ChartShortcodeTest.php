<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\ChartShortcode;
use PHPUnit\Framework\TestCase;

final class ChartShortcodeTest extends TestCase
{
    public function testDeferAndRestoreRendersSvgFigure(): void
    {
        $shortcode = new ChartShortcode();
        $markdown = "Intro\n\n:::chart\n{\"type\":\"bar\",\"labels\":[\"A\",\"B\"],\"values\":[10,20]}\n:::\n\nTail";

        [$deferred, $renders] = $shortcode->deferBlocks($markdown);
        $this->assertStringContainsString('<!-- paginium-chart:0 -->', $deferred);
        $this->assertCount(1, $renders);

        $html = $shortcode->restoreDeferred('<p>Intro</p><!-- paginium-chart:0 -->', $renders);
        $this->assertStringContainsString('paginium-chart', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function testValidateBlocksRejectsMismatchedSeries(): void
    {
        $shortcode = new ChartShortcode();
        $markdown = ":::chart\n{\"type\":\"bar\",\"labels\":[\"A\"],\"values\":[1,2]}\n:::";

        $this->assertNotNull($shortcode->validateBlocks($markdown));
    }

    public function testValidateBlocksAcceptsLineChart(): void
    {
        $shortcode = new ChartShortcode();
        $markdown = ":::chart\n{\"type\":\"line\",\"labels\":[\"Q1\",\"Q2\"],\"values\":[3,5]}\n:::";

        $this->assertNull($shortcode->validateBlocks($markdown));
    }
}
