<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\TrustedContentDetector;
use PHPUnit\Framework\TestCase;

final class TrustedContentDetectorTest extends TestCase
{
    public function testCountsHtmlSafeAndEmbedBlocks(): void
    {
        $detector = new TrustedContentDetector();
        $markdown = "Intro\n\n:::html-safe\n<div></div>\n:::\n\n:::embed\nprovider: youtube\nid: dQw4w9WgXcQ\n:::\n";

        $this->assertTrue($detector->hasTrustedBlocks($markdown));
        $counts = $detector->countBlocks($markdown);
        $this->assertSame(1, $counts['html_safe']);
        $this->assertSame(1, $counts['embed']);
    }

    public function testPlainMarkdownHasNoTrustedBlocks(): void
    {
        $detector = new TrustedContentDetector();

        $this->assertFalse($detector->hasTrustedBlocks("## Hello\n\nPlain text."));
        $counts = $detector->countBlocks("## Hello\n\nPlain text.");
        $this->assertSame(0, $counts['html_safe']);
        $this->assertSame(0, $counts['embed']);
    }
}
