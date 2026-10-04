<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\GalleryCarouselRenderer;
use PHPUnit\Framework\TestCase;

final class GalleryCarouselRendererTest extends TestCase
{
    public function testEmitsIslandMarkerAndDataAttrs(): void
    {
        $html = GalleryCarouselRenderer::render([
            'title' => 'Highlights',
            'tag' => 'web',
            'layout' => 'hero-strip',
            'effect' => 'cinematic',
            'autoplay' => 'false',
            'modal-caption-style' => 'overlay',
        ]);

        $this->assertStringContainsString('pg-island--gallery-carousel', $html);
        $this->assertStringContainsString('data-island="gallery-carousel"', $html);
        $this->assertStringContainsString('data-tag="web"', $html);
        $this->assertStringContainsString('data-title="Highlights"', $html);
        $this->assertStringContainsString('data-layout="hero-strip"', $html);
        $this->assertStringContainsString('data-effect="cinematic"', $html);
        $this->assertStringContainsString('data-autoplay="false"', $html);
        $this->assertStringContainsString('data-modal-caption-style="overlay"', $html);
    }

    public function testRejectsInvalidEnums(): void
    {
        $html = GalleryCarouselRenderer::render([
            'layout' => 'grid',
            'effect' => 'explosive',
            'modal-caption-style' => 'top',
        ]);

        $this->assertStringContainsString('data-layout="slider"', $html);
        $this->assertStringContainsString('data-effect="subtle"', $html);
        $this->assertStringNotContainsString('data-modal-caption-style=', $html);
    }
}
