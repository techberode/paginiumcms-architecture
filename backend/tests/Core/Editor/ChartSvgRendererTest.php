<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\ChartSvgRenderer;
use PHPUnit\Framework\TestCase;

final class ChartSvgRendererTest extends TestCase
{
    public function testRenderBarChartProducesSvg(): void
    {
        $renderer = new ChartSvgRenderer();
        [$spec, $error] = $renderer->parseSpec('{"type":"bar","labels":["A","B"],"values":[10,20]}');

        $this->assertNull($error);
        $this->assertIsArray($spec);
        $svg = $renderer->render($spec);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<rect', $svg);
    }

    public function testParseSpecRejectsUnknownType(): void
    {
        $renderer = new ChartSvgRenderer();
        [, $error] = $renderer->parseSpec('{"type":"pie","labels":["A"],"values":[1]}');

        $this->assertNotNull($error);
    }
}
