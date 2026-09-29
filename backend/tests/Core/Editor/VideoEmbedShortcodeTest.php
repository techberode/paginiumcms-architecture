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

    public function testExpandsVideoWithCaption(): void
    {
        $expander = new VideoEmbedShortcode();
        $markdown = <<<MD
:::video
src: /storage/app/content/media/demo.mp4
caption: Obr. 1.1 demo
:::
MD;

        $html = $expander->expand($markdown);

        $this->assertStringContainsString('<figure class="paginium-figure paginium-figure--video">', $html);
        $this->assertStringContainsString('<figcaption>Obr. 1.1 demo</figcaption>', $html);
    }

    public function testExpandsVideoWithCaptionAbove(): void
    {
        $expander = new VideoEmbedShortcode();
        $markdown = <<<MD
:::video
src: /storage/app/content/media/demo.mp4
caption: Nad videom
captionPosition: above
:::
MD;

        $html = $expander->expand($markdown);

        $this->assertStringContainsString('paginium-figure--caption-top', $html);
        $this->assertLessThan(strpos($html, '<video'), strpos($html, '<figcaption'));
    }
}
