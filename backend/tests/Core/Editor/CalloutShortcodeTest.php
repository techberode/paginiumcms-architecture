<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\CalloutShortcode;
use PHPUnit\Framework\TestCase;

final class CalloutShortcodeTest extends TestCase
{
    public function testDeferAndRestoreNoteCallout(): void
    {
        $expander = new CalloutShortcode();
        $markdown = "Intro\n\n:::note\nImportant **text**\n:::\n\nTail";

        [$deferred, $renders] = $expander->deferBlocks($markdown);
        $this->assertStringContainsString('<!-- paginium-callout:note:0 -->', $deferred);
        $this->assertCount(1, $renders);

        $html = $expander->restoreDeferred('<p>Intro</p><!-- paginium-callout:note:0 -->', $renders);
        $this->assertStringContainsString('paginium-callout--note', $html);
        $this->assertStringContainsString('<strong>text</strong>', $html);
    }

    public function testValidateBlocksRejectsScriptInBody(): void
    {
        $expander = new CalloutShortcode();
        $markdown = ":::warning\n<script>alert(1)</script>\n:::";

        $this->assertNotNull($expander->validateBlocks($markdown));
    }
}
