<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\SectionBandRenderer;
use PHPUnit\Framework\TestCase;

final class SectionBandRendererTest extends TestCase
{
    public function testRendersInnerHtmlWithContainedLayout(): void
    {
        $html = SectionBandRenderer::render(
            ['anchor' => 'portfolio', 'layout' => 'contained', 'radius' => 'rounded'],
            '<p>Hello</p>'
        );

        $this->assertStringContainsString('pg-section-band', $html);
        $this->assertStringContainsString('id="portfolio"', $html);
        $this->assertStringContainsString('pg-section-band--radius-rounded', $html);
        $this->assertStringNotContainsString('pg-section-band--layout-contained', $html);
        $this->assertStringContainsString('<p>Hello</p>', $html);
    }

    public function testBackgroundImageOverlayAndTwoColumnLayout(): void
    {
        $html = SectionBandRenderer::render(
            [
                'bg-image' => '/storage/app/content/media/hero.jpg',
                'overlay' => 'dark',
                'layout' => 'two-column',
                'bg-attachment' => 'fixed',
            ],
            '<p>Col</p>'
        );

        $this->assertStringContainsString('pg-section-band__image', $html);
        $this->assertStringContainsString('/storage/app/content/media/hero.jpg', $html);
        $this->assertStringContainsString('pg-section-band--overlay-dark', $html);
        $this->assertStringContainsString('pg-section-band__inner--two-column', $html);
        $this->assertStringContainsString('pg-section-band--bg-fixed', $html);
    }

    public function testRevealAndHoverEffectClasses(): void
    {
        $scroll = SectionBandRenderer::render(['reveal' => 'scroll', 'hover-effect' => 'lift'], '<p>x</p>');
        $this->assertStringContainsString('pg-reveal', $scroll);
        $this->assertStringContainsString('pg-section-band--hover-lift', $scroll);

        $static = SectionBandRenderer::render(['reveal' => 'none', 'hover-effect' => 'glow'], '<p>x</p>');
        $this->assertStringNotContainsString('pg-reveal', $static);
        $this->assertStringContainsString('pg-section-band--hover-glow', $static);
    }

    public function testRejectsInvalidAnchorAndExternalImage(): void
    {
        $html = SectionBandRenderer::render(
            [
                'anchor' => 'bad id',
                'bg-image' => 'https://evil.example/x.jpg',
                'tone' => 'muted',
            ],
            ''
        );

        $this->assertStringNotContainsString('id="', $html);
        $this->assertStringNotContainsString('evil.example', $html);
        $this->assertStringContainsString('pg-section-band--tone-muted', $html);
    }
}
