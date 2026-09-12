<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\VideoEmbedShortcode;
use PHPUnit\Framework\TestCase;

final class VideoEmbedShortcodeTest extends TestCase
{
    public function testExpandsBlockVideoShortcode(): void
    {
        $expander = new VideoEmbedShortcode();
        $markdown = <<<MD
Intro

:::video
src: /storage/app/content/media/demo.mp4
:::

Tail
MD;

        $html = $expander->expand($markdown);

        $this->assertStringContainsString('<video src="/storage/app/content/media/demo.mp4"', $html);
        $this->assertStringContainsString('controls', $html);
        $this->assertStringNotContainsString(':::video', $html);
    }

    public function testRejectsExternalVideoSrc(): void
    {
        $expander = new VideoEmbedShortcode();

        $this->assertSame('', $expander->sanitizeMediaUrl('https://evil.example/clip.mp4'));
        $this->assertSame('/storage/app/content/media/x.mp4', $expander->sanitizeMediaUrl('/storage/app/content/media/x.mp4'));
    }
}
