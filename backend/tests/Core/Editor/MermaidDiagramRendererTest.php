<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\MermaidDiagramRenderer;
use PaginiumCMS\Core\Editor\Services\MermaidSvgSanitizer;
use PHPUnit\Framework\TestCase;

final class MermaidDiagramRendererTest extends TestCase
{
    public function testNormalizeGraphTdSingleLine(): void
    {
        $renderer = new MermaidDiagramRenderer();
        $normalized = $renderer->normalizeSource('graph TD; A-->B; C-->D');

        $this->assertSame("flowchart TD\nA-->B\nC-->D", $normalized);
    }

    public function testRenderSvgStripsScriptPayload(): void
    {
        $sanitizer = new MermaidSvgSanitizer();
        $this->assertSame('', $sanitizer->sanitize('<svg><script>alert(1)</script></svg>'));
    }
}
