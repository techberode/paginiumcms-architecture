<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\LatestArticlesRenderer;
use PHPUnit\Framework\TestCase;

final class LatestArticlesRendererTest extends TestCase
{
    public function testRenderIncludesArticleLinks(): void
    {
        $html = LatestArticlesRenderer::render(
            ['label' => 'Novinky', 'speed' => 'normal'],
            [['slug' => 'nova-sprava', 'title' => 'Nová správa']]
        );

        $this->assertStringContainsString('pg-news-ticker', $html);
        $this->assertStringContainsString('Novinky', $html);
        $this->assertStringContainsString('/blog/nova-sprava', $html);
        $this->assertStringContainsString('Nová správa', $html);
    }
}
