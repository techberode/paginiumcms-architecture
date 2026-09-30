<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\CtaBannerRenderer;
use PHPUnit\Framework\TestCase;

final class CtaBannerRendererTest extends TestCase
{
    public function testShowSubtitleFalseOmitsSubtitle(): void
    {
        $html = CtaBannerRenderer::render([
            'title' => 'Title',
            'subtitle' => 'Sub',
            'show-subtitle' => 'false',
        ]);

        $this->assertStringContainsString('pg-cta-title', $html);
        $this->assertStringNotContainsString('pg-cta-subtitle', $html);
    }
}
