<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\MermaidShortcode;
use PHPUnit\Framework\TestCase;

final class MermaidShortcodeTest extends TestCase
{
    public function testDeferAndRestoreRendersSvgFigure(): void
    {
        $shortcode = new MermaidShortcode();
        $markdown = "Intro\n\n:::mermaid\nflowchart TD\n    A --> B\n:::\n\nTail";

        [$deferred, $renders] = $shortcode->deferBlocks($markdown);
        $this->assertStringContainsString('<!-- paginium-mermaid:0 -->', $deferred);
        $this->assertCount(1, $renders);

        $html = $shortcode->restoreDeferred('<p>Intro</p><!-- paginium-mermaid:0 -->', $renders);
        $this->assertStringContainsString('paginium-mermaid', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function testValidateBlocksRejectsHtmlInBody(): void
    {
        $shortcode = new MermaidShortcode();
        $markdown = ":::mermaid\n<script>alert(1)</script>\n:::";

        $this->assertNotNull($shortcode->validateBlocks($markdown));
    }

    public function testValidateBlocksAcceptsGraphTdAlias(): void
    {
        $shortcode = new MermaidShortcode();
        $markdown = ":::mermaid\ngraph TD\nA --> B\n:::";

        $this->assertNull($shortcode->validateBlocks($markdown));
    }
}
