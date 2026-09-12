<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\ExternalEmbedShortcode;
use PHPUnit\Framework\TestCase;

final class ExternalEmbedShortcodeTest extends TestCase
{
    public function testExpandYoutubeBlockUsesNocookieHost(): void
    {
        $expander = new ExternalEmbedShortcode();
        $markdown = "Intro\n\n:::embed\nprovider: youtube\nid: dQw4w9WgXcQ\n:::\n\nTail";

        $expanded = $expander->expand($markdown);

        $this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $expanded);
        $this->assertStringContainsString('class="paginium-external-embed"', $expanded);
        $this->assertStringContainsString('sandbox=', $expanded);
    }

    public function testExpandInlineVimeoBlock(): void
    {
        $expander = new ExternalEmbedShortcode();
        $markdown = ':::embed provider="vimeo" id="123456789" :::';

        $expanded = $expander->expand($markdown);

        $this->assertStringContainsString('player.vimeo.com/video/123456789', $expanded);
    }

    public function testInvalidYoutubeIdExpandsToEmpty(): void
    {
        $expander = new ExternalEmbedShortcode();
        $markdown = ":::embed\nprovider: youtube\nid: short\n:::";

        $this->assertSame('', $expander->expand($markdown));
    }

    public function testValidateBlocksRejectsDisabledProvider(): void
    {
        $expander = new ExternalEmbedShortcode();
        $markdown = ":::embed\nprovider: vimeo\nid: 123456789\n:::";

        $error = $expander->validateBlocks($markdown, ['youtube']);

        $this->assertNotNull($error);
        $this->assertStringContainsString('vimeo', strtolower((string) $error));
    }

    public function testIsAllowedIframeSrcAcceptsKnownHosts(): void
    {
        $this->assertTrue(
            ExternalEmbedShortcode::isAllowedIframeSrc('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        );
        $this->assertTrue(
            ExternalEmbedShortcode::isAllowedIframeSrc('https://player.vimeo.com/video/123456789')
        );
        $this->assertFalse(
            ExternalEmbedShortcode::isAllowedIframeSrc('https://evil.example/embed/1')
        );
    }
}
