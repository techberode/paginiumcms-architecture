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
}
