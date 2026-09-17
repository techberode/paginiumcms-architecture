<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\LandingHeroRenderer;
use PHPUnit\Framework\TestCase;

final class LandingHeroRendererTest extends TestCase
{
    public function testRendersDamImageAndMutedVideo(): void
    {
        $html = LandingHeroRenderer::render([
            'title' => 'Studio',
            'subtitle' => 'Work',
            'cta' => 'Contact',
            'href' => '/contact',
            'image' => '/storage/app/content/media/hero.jpg',
            'src' => '/storage/app/content/media/hero.mp4',
            'srcmobile' => '/storage/app/content/media/hero-sm.webm',
        ]);

        $this->assertStringContainsString('pg-hero-media', $html);
        $this->assertStringContainsString('pg-hero-photo', $html);
        $this->assertStringContainsString('/storage/app/content/media/hero.jpg', $html);
        $this->assertStringContainsString('<video class="pg-hero-video"', $html);
        $this->assertStringContainsString(' muted', $html);
        $this->assertStringContainsString(' loop', $html);
        $this->assertStringContainsString(' playsinline', $html);
        $this->assertStringContainsString(' autoplay', $html);
        $this->assertStringNotContainsString('controls', $html);
        $this->assertStringContainsString('max-width: 767px', $html);
        $this->assertStringContainsString('Studio', $html);
    }

    public function testDropsExternalMediaUrls(): void
    {
        $html = LandingHeroRenderer::render([
            'title' => 'X',
            'image' => 'https://evil.example/x.jpg',
            'src' => 'https://youtube.com/watch?v=1',
        ]);

        $this->assertStringNotContainsString('evil.example', $html);
        $this->assertStringNotContainsString('youtube.com', $html);
        $this->assertStringNotContainsString('pg-hero-video', $html);
        $this->assertStringContainsString('pg-hero-title', $html);
    }

    public function testHasMediaRequiresDamPath(): void
    {
        $this->assertFalse(LandingHeroRenderer::hasMedia(['src' => 'https://cdn.example/x.mp4']));
        $this->assertTrue(LandingHeroRenderer::hasMedia(['image' => '/api/media/file/hero.jpg']));
    }
}
