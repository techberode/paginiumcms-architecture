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

    public function testMapEmbedAllowsGoogleEmbedOnly(): void
    {
        $allowed = (new WidgetCatalog())->render(
            ' type="map-embed" title="HQ" src="https://www.google.com/maps/embed?pb=test" height="300"',
            ''
        );
        $this->assertStringContainsString('pg-widget-map-frame', $allowed);
        $this->assertStringContainsString('https://www.google.com/maps/embed?pb=test', $allowed);

        $blocked = (new WidgetCatalog())->render(
            ' type="map-embed" title="X" src="https://evil.example/map" height="300"',
            ''
        );
        $this->assertStringContainsString('pg-widget-empty', $blocked);
    }

    public function testDataTableRendersRows(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="data-table" title="Plans" headers="Name | Price" rows="Basic | 9 | Pro | 29"',
            ''
        );
        $this->assertStringContainsString('pg-widget-table', $html);
        $this->assertStringContainsString('<th scope="col">Name</th>', $html);
        $this->assertStringContainsString('<td>Pro</td>', $html);
    }

    public function testChecklistRendersMarkedItems(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="checklist" title="Plan" items="A | B" tone="success"',
            ''
        );
        $this->assertStringContainsString('pg-widget-checklist', $html);
        $this->assertStringContainsString('pg-widget-check-item', $html);
    }

    public function testStatDuoRendersTwoCells(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="stat-duo" label1="Uptime" value1="99%" label2="Posts" value2="12" tone="primary"',
            ''
        );
        $this->assertStringContainsString('pg-widget-stat-duo', $html);
        $this->assertStringContainsString('99%', $html);
        $this->assertStringContainsString('Posts', $html);
    }

    public function testProfileRendersAvatarWhenAllowListed(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="profile" name="Alex" role="Editor" avatar="/storage/app/content/media/defaults/author-avatar.png" avatar-size="lg" href=""',
            ''
        );
        $this->assertStringContainsString('pg-widget-profile', $html);
        $this->assertStringContainsString('pg-widget-avatar-size-lg', $html);
        $this->assertStringContainsString('author-avatar.png', $html);
    }

    public function testFaqRendersAccordionDetails(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="faq" title="Help" items="Q one?::Answer one | Q two?::Answer two"',
            ''
        );
        $this->assertStringContainsString('pg-widget-faq', $html);
        $this->assertStringContainsString('<details class="pg-faq-item">', $html);
        $this->assertStringContainsString('Q one?', $html);
        $this->assertStringContainsString('Answer two', $html);
    }

    public function testAvatarWidgetBlocksExternalSrc(): void
    {
        $html = (new WidgetCatalog())->render(
            ' type="avatar" src="https://evil.example/x.png" alt="X" size="md" href=""',
            ''
        );
        $this->assertStringContainsString('pg-widget-empty', $html);
    }
}
