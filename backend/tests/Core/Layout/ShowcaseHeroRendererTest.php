<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\ShowcaseHeroRenderer;
use PHPUnit\Framework\TestCase;

final class ShowcaseHeroRendererTest extends TestCase
{
    public function testRendersFullHeroByDefault(): void
    {
        $html = ShowcaseHeroRenderer::render([
            'badge' => 'HYBRID',
            'title' => 'Title',
            'subtitle' => 'Sub',
            'terminal' => 'curl /api/health',
            'cta' => 'Go',
            'href' => '#x',
            'cta2' => 'Blog',
            'href2' => '/blog',
        ]);

        $this->assertStringContainsString('pg-showcase-badge', $html);
        $this->assertStringContainsString('pg-showcase-terminal', $html);
        $this->assertStringContainsString('pg-btn-primary', $html);
        $this->assertStringContainsString('pg-btn-ghost', $html);
    }

    public function testShowTerminalFalseOmitsTerminalBlock(): void
    {
        $html = ShowcaseHeroRenderer::render([
            'title' => 'Title',
            'terminal' => 'curl /api/health',
            'show-terminal' => 'false',
        ]);

        $this->assertStringNotContainsString('pg-showcase-terminal', $html);
        $this->assertStringContainsString('pg-showcase-title', $html);
    }

    public function testEmptyTerminalOmitsTerminalEvenWhenShowTrue(): void
    {
        $html = ShowcaseHeroRenderer::render([
            'title' => 'Title',
            'show-terminal' => 'true',
        ]);

        $this->assertStringNotContainsString('pg-showcase-terminal', $html);
    }

    public function testShowCta2FalseOmitsSecondaryButton(): void
    {
        $html = ShowcaseHeroRenderer::render([
            'title' => 'Title',
            'cta' => 'Primary',
            'href' => '#',
            'cta2' => 'Secondary',
            'href2' => '/blog',
            'show-cta2' => 'false',
        ]);

        $this->assertStringContainsString('pg-btn-primary', $html);
        $this->assertStringNotContainsString('pg-btn-ghost', $html);
    }
}
