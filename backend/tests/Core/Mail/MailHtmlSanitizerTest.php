<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PaginiumCMS\Core\Mail\Services\MailHtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class MailHtmlSanitizerTest extends TestCase
{
    public function testBlocksRemoteTrackingPixelByDefault(): void
    {
        $html = MailHtmlSanitizer::document(
            '<html><body><img src="https://tracker.example/pixel.gif?uid=1" alt="x"></body></html>'
        );

        $this->assertStringContainsString('data-pg-blocked-src="https://tracker.example/pixel.gif?uid=1"', $html);
        $this->assertStringContainsString('src="data:image/gif;base64,', $html);
        $this->assertDoesNotMatchRegularExpression('/\ssrc="https?:\/\//', $html);
    }

    public function testAllowsRemoteImagesWhenRequested(): void
    {
        $url = 'https://cdn.example/logo.png';
        $html = MailHtmlSanitizer::document(
            '<html><body><img src="' . $url . '" alt="logo"></body></html>',
            false
        );

        $this->assertStringContainsString($url, $html);
        $this->assertStringNotContainsString('data-pg-blocked-src', $html);
    }
}
