<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Services;

use PaginiumCMS\Core\Security\Services\HtmlDomSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlDomSanitizerTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function dangerousHrefs(): array
    {
        return [
            'plain' => ['javascript:alert(1)'],
            'uppercase' => ['JaVaScRiPt:alert(1)'],
            'leading C0 0x01' => ["\x01javascript:alert(1)"],
            'leading C0 0x1f' => ["\x1fjavascript:alert(1)"],
            'leading space + C0' => [" \x01 javascript:alert(1)"],
            'tab inside scheme' => ["java\tscript:alert(1)"],
            'entity tab' => ['java&#9;script:alert(1)'],
            'entity newline' => ['java&#10;script:alert(1)'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'data html' => ['data:text/html,<script>alert(1)</script>'],
        ];
    }

    #[DataProvider('dangerousHrefs')]
    public function testDangerousHrefNeverSurvivesAsJavascriptScheme(string $href): void
    {
        $out = (new HtmlDomSanitizer())->sanitize('<a href="' . $href . '">x</a>', ['a']);

        $this->assertDoesNotMatchRegularExpression('/href="[\x00-\x20]*java\s*script:/i', $out);
        $this->assertDoesNotMatchRegularExpression('/href="[\x00-\x20]*vbscript:/i', $out);
        $this->assertDoesNotMatchRegularExpression('/href="[\x00-\x20]*data:/i', $out);
    }

    public function testSafeLinksAreKept(): void
    {
        $out = (new HtmlDomSanitizer())->sanitize(
            '<a href="https://example.com/a">a</a><a href="/blog/post">b</a><a href="#top">c</a><a href="mailto:a@b.sk">d</a>',
            ['a']
        );

        $this->assertStringContainsString('href="https://example.com/a"', $out);
        $this->assertStringContainsString('href="/blog/post"', $out);
        $this->assertStringContainsString('href="#top"', $out);
        $this->assertStringContainsString('href="mailto:a@b.sk"', $out);
    }
}
