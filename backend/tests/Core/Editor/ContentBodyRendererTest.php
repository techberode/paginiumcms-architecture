<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PHPUnit\Framework\TestCase;

final class ContentBodyRendererTest extends TestCase
{
    private function renderer(MarkdownContentParserInterface $markdown): ContentBodyRenderer
    {
        $settings = $this->createMock(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['sanitizeHtmlOnSave' => false]);
        $sanitizer = new ContentSecuritySanitizer($settings);

        return new ContentBodyRenderer($markdown, new TiptapHtmlRenderer(), $sanitizer);
    }

    public function testUsesCachedHtmlForTiptapJson(): void
    {
        $markdown = $this->createMock(MarkdownContentParserInterface::class);
        $renderer = $this->renderer($markdown);

        $html = $renderer->resolveHtml(
            '{"type":"doc","content":[]}',
            'tiptap_json',
            '<p>cached</p>'
        );

        $this->assertSame('<p>cached</p>', $html);
    }

    public function testRendersTiptapJsonWhenCacheMissing(): void
    {
        $markdown = $this->createMock(MarkdownContentParserInterface::class);
        $renderer = $this->renderer($markdown);

        $json = json_encode([
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Hello',
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $html = $renderer->resolveHtml($json, 'tiptap_json', null);

        $this->assertStringContainsString('<p>Hello</p>', $html);
    }

    public function testNormalizesTiptapJsonFromBody(): void
    {
        $markdown = $this->createMock(MarkdownContentParserInterface::class);
        $renderer = $this->renderer($markdown);

        $format = $renderer->normalizeContentFormat(
            null,
            '{"type":"doc","content":[]}'
        );

        $this->assertSame('tiptap_json', $format);
    }

    public function testDelegatesMarkdownToParser(): void
    {
        $markdown = $this->createMock(MarkdownContentParserInterface::class);
        $markdown->expects($this->once())
            ->method('parse')
            ->with('**bold**')
            ->willReturn('<p><strong>bold</strong></p>');

        $renderer = $this->renderer($markdown);

        $html = $renderer->resolveHtml('**bold**', 'markdown', null);

        $this->assertSame('<p><strong>bold</strong></p>', $html);
    }
}
