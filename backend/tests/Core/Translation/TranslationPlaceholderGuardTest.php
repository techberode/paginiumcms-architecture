<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Translation;

use PaginiumCMS\Core\Translation\Services\TranslationPlaceholderGuard;
use PHPUnit\Framework\TestCase;

final class TranslationPlaceholderGuardTest extends TestCase
{
    public function testProtectsCodeUrlsAndShortcodes(): void
    {
        $guard = new TranslationPlaceholderGuard();
        $source = "See https://example.com and [staff-card id=\"1\"] plus `inline` and\n```\ncode\n```\nmedia_abc123";
        $protected = $guard->protect($source);

        $this->assertStringNotContainsString('https://example.com', $protected['text']);
        $this->assertStringNotContainsString('[staff-card', $protected['text']);
        $this->assertStringNotContainsString('media_abc123', $protected['text']);
        $this->assertSame($source, $guard->restore($protected['text'], $protected['tokens']));
    }

    public function testProtectsClosingShortcodeTagsWhileTranslatingInnerText(): void
    {
        $guard = new TranslationPlaceholderGuard();
        $source = '[alert-box tone="info"]Important notice[/alert-box]';
        $protected = $guard->protect($source, false);

        $this->assertStringNotContainsString('[alert-box', $protected['text']);
        $this->assertStringNotContainsString('[/alert-box]', $protected['text']);
        $this->assertStringContainsString('Important notice', $protected['text']);
        $this->assertSame($source, $guard->restore($protected['text'], $protected['tokens']));
    }

    public function testPairedShortcodeBlockModeProtectsEntireBlock(): void
    {
        $guard = new TranslationPlaceholderGuard();
        $source = "Intro\n[alert-box tone=\"info\"]Do not translate[/alert-box]\nOutro";
        $protected = $guard->protect($source, true);

        $this->assertStringContainsString('Intro', $protected['text']);
        $this->assertStringContainsString('Outro', $protected['text']);
        $this->assertStringNotContainsString('Do not translate', $protected['text']);
        $this->assertStringNotContainsString('[/alert-box]', $protected['text']);
        $this->assertSame($source, $guard->restore($protected['text'], $protected['tokens']));
    }

    public function testFencedCodeInsideBodyIsNotSentToProvider(): void
    {
        $guard = new TranslationPlaceholderGuard();
        $source = "Lead\n```php\necho 1;\n```\n[alert-box]x[/alert-box]";
        $protected = $guard->protect($source);

        $this->assertStringNotContainsString('echo 1', $protected['text']);
        $this->assertStringNotContainsString('[/alert-box]', $protected['text']);
        $this->assertSame($source, $guard->restore($protected['text'], $protected['tokens']));
    }
}
