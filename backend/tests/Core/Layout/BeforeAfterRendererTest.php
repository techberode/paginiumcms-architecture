<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\BeforeAfterRenderer;
use PHPUnit\Framework\TestCase;

final class BeforeAfterRendererTest extends TestCase
{
    public function testRenderEmitsIslandWithSanitizedUrls(): void
    {
        $html = BeforeAfterRenderer::render([
            'before' => '/storage/media/a.jpg',
            'after' => '/storage/media/b.jpg',
            'label-before' => 'Pred',
            'label-after' => 'Po',
        ]);

        $this->assertStringContainsString('data-island="before-after"', $html);
        $this->assertStringContainsString('data-before="/storage/media/a.jpg"', $html);
        $this->assertStringContainsString('data-after="/storage/media/b.jpg"', $html);
        $this->assertStringContainsString('data-label-before="Pred"', $html);
    }

    public function testMissingImageReturnsEmpty(): void
    {
        $this->assertSame('', BeforeAfterRenderer::render(['before' => '/storage/media/a.jpg']));
    }
}
