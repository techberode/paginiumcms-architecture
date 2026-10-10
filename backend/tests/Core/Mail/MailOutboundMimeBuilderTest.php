<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PaginiumCMS\Core\Mail\Services\MailOutboundMimeBuilder;
use PHPUnit\Framework\TestCase;

final class MailOutboundMimeBuilderTest extends TestCase
{
    public function testBuildsHtmlRfc822(): void
    {
        $raw = MailOutboundMimeBuilder::buildRfc822(
            'editor@site.test',
            'Editor',
            ['guest@example.com'],
            'Hello',
            '<p>Hi</p>'
        );

        $this->assertStringContainsString('Subject: ', $raw);
        $this->assertStringContainsString('To: <guest@example.com>', $raw);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $raw);
        $this->assertStringContainsString(base64_encode('<p>Hi</p>'), str_replace("\r\n", '', $raw));
    }

    public function testBase64HtmlPreservesUtf8Diacritics(): void
    {
        $html = '<p>Prehľad návštevnosti · 📊</p>';
        $raw = MailOutboundMimeBuilder::buildRfc822(
            'noreply@site.test',
            'PaginiumCMS',
            ['admin@site.test'],
            'Report',
            $html
        );

        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $raw);
        $encoded = base64_encode($html);
        $this->assertStringContainsString($encoded, str_replace("\r\n", '', $raw));
    }

    public function testBuildsMultipleToRecipients(): void
    {
        $raw = MailOutboundMimeBuilder::buildRfc822(
            'editor@site.test',
            'Editor',
            ['a@example.com', 'b@example.com'],
            'Hello',
            '<p>Hi</p>'
        );

        $this->assertStringContainsString('To: <a@example.com>, <b@example.com>', $raw);
    }
}
