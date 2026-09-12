<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\HtmlSafeShortcode;
use PHPUnit\Framework\TestCase;

final class HtmlSafeShortcodeTest extends TestCase
{
    public function testExpandWrapsHtmlSafeBlock(): void
    {
        $expander = new HtmlSafeShortcode();
        $markdown = "Intro\n\n:::html-safe\n<div>ok</div>\n:::\n\nTail";

        $expanded = $expander->expand($markdown);

        $this->assertStringContainsString('<div class="paginium-html-safe"><div>ok</div></div>', $expanded);
    }

    public function testStripBlocksRemovesHtmlSafeForValidation(): void
    {
        $expander = new HtmlSafeShortcode();
        $markdown = "Text\n\n:::html-safe\n<div>x</div>\n:::\n";

        $this->assertSame("Text\n\n\n", $expander->stripBlocks($markdown));
    }
}
