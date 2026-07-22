<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\LogSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Overuje anti log-injection sanitizáciu (audit C11).
 */
final class LogSanitizerTest extends TestCase
{
    public function testStripsCrLfIntoSingleSpace(): void
    {
        // Klasický log-injection pokus: falošný „admin login" na novom riadku.
        $input = "GET /x\r\nadmin login OK";
        $this->assertSame('GET /x admin login OK', LogSanitizer::value($input));
    }

    public function testStripsControlAndDelChars(): void
    {
        // Odstráni sa control beh vrátane ESC (\x1B) a DEL (\x7F); tlačiteľné
        // zvyšky ANSI sekvencie ("[31m") ostávajú ako neškodný text (bez ESC
        // ich terminál neinterpretuje).
        $input = "abc\x00\x07\x1B[31m\x7Fdef";
        $this->assertSame('abc [31m def', LogSanitizer::value($input));
    }

    public function testTrimsResult(): void
    {
        $this->assertSame('clean', LogSanitizer::value("\n\t clean \r\n"));
    }

    public function testMaxLengthTruncates(): void
    {
        $this->assertSame('abcde', LogSanitizer::value('abcdefghij', 5));
    }

    public function testPlainValueUnchanged(): void
    {
        $this->assertSame('Mozilla/5.0 (X11)', LogSanitizer::value('Mozilla/5.0 (X11)'));
    }

    public function testContextSanitizesStringsAndKeepsScalars(): void
    {
        $context = [
            'user_agent' => "curl/8\r\nX-Injected: 1",
            'status' => 200,
            'ok' => true,
            'nested' => ['q' => "a\nb"],
        ];

        $clean = LogSanitizer::context($context);

        $this->assertSame('curl/8 X-Injected: 1', $clean['user_agent']);
        $this->assertSame(200, $clean['status']);
        $this->assertTrue($clean['ok']);
        $this->assertSame('a b', $clean['nested']['q']);
    }

    public function testContextSanitizesStringKeys(): void
    {
        $clean = LogSanitizer::context(["bad\r\nkey" => 'v']);
        $this->assertArrayHasKey('bad key', $clean);
    }
}
