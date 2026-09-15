<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\WidgetCatalog;
use PHPUnit\Framework\TestCase;

final class WidgetCatalogTest extends TestCase
{
    public function testCatalogIncludesKpiAndKpiRow(): void
    {
        $catalog = new WidgetCatalog();
        $ids = array_map(static fn (array $row): string => (string) $row['id'], $catalog->catalog());

        $this->assertContains('kpi', $ids);
        $this->assertContains('kpi-row', $ids);
        $this->assertTrue($catalog->isKnownType('progress'));
        $this->assertFalse($catalog->isKnownType('falcon-sales'));
    }

    public function testKpiRendersEscapedHtml(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="kpi" title="Visitors <b>" value="12k" delta="+8%" hint="Week" tone="success"',
            ''
        );

        $this->assertStringContainsString('pg-widget-kpi', $html);
        $this->assertStringContainsString('pg-widget-tone-success', $html);
        $this->assertStringContainsString('Visitors &lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function testUnknownTypeLeavesTicket(): void
    {
        $raw = ' type="not-a-widget" title="X"';
        $html = (new WidgetCatalog())->render($raw, 'inner');

        $this->assertSame('[widget' . $raw . ']inner[/widget]', $html);
        $this->assertSame('[widget' . $raw . '/]', (new WidgetCatalog())->render($raw, ''));
    }

    public function testRejectsJavascriptHref(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="cta" title="Go" subtitle="" cta="Open" href="javascript:alert(1)"',
            ''
        );

        $this->assertStringContainsString('href="#"', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }
}
