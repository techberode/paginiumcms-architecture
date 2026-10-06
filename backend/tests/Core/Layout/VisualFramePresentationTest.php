<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\VisualFramePresentation;
use PHPUnit\Framework\TestCase;

final class VisualFramePresentationTest extends TestCase
{
    public function testRenderMotionFadeUp(): void
    {
        $html = VisualFramePresentation::render(
            ['motion' => 'fade-up', 'align' => 'center'],
            '<p>Hi</p>'
        );

        $this->assertStringContainsString('pg-motion--fade-up', $html);
        $this->assertStringContainsString('pg-visual-frame', $html);
    }

    public function testRenderMotionStaggerWithShortDelay(): void
    {
        $html = VisualFramePresentation::render(
            ['motion' => 'stagger', 'motion-delay' => 'short'],
            '<p>A</p><p>B</p>'
        );

        $this->assertStringContainsString('pg-motion--stagger', $html);
        $this->assertStringContainsString('pg-motion-delay-short', $html);
    }

    public function testIgnoresUnknownMotion(): void
    {
        $html = VisualFramePresentation::render(['motion' => 'zoom'], '<p>X</p>');
        $this->assertStringNotContainsString('pg-motion', $html);
    }
}
