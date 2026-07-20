<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PHPUnit\Framework\TestCase;

final class TiptapHtmlRendererTest extends TestCase
{
    public function testRendersParagraphWithBoldText(): void
    {
        $renderer = new TiptapHtmlRenderer();
        $json = json_encode([
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Hello',
                    'marks' => [['type' => 'bold']],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $html = $renderer->render($json);

        $this->assertStringContainsString('<strong>Hello</strong>', $html);
    }

    public function testStripsUnsafeImageUrl(): void
    {
        $renderer = new TiptapHtmlRenderer();
        $json = json_encode([
            'type' => 'doc',
            'content' => [[
                'type' => 'image',
                'attrs' => ['src' => 'javascript:alert(1)', 'alt' => 'x'],
            ]],
        ], JSON_THROW_ON_ERROR);

        $html = $renderer->render($json);

        $this->assertSame('', $html);
    }
}
