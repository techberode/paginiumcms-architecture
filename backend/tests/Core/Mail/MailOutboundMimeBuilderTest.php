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
        $this->assertStringContainsString('Content-Transfer-Encoding: quoted-printable', $raw);
        $this->assertStringContainsString('<p>Hi</p>', $raw);
    }

    public function testQuotedPrintableHtmlPreservesUtf8Diacritics(): void
    {
        $html = '<p>Prehľad návštevnosti</p>';
        $raw = MailOutboundMimeBuilder::buildRfc822(
            'noreply@site.test',
            'PaginiumCMS',
            ['admin@site.test'],
            'Report',
            $html
        );

        $this->assertStringContainsString('Content-Transfer-Encoding: quoted-printable', $raw);
        $this->assertStringNotContainsString('Content-Transfer-Encoding: base64', $raw);
        $decoded = quoted_printable_decode(explode("\r\n\r\n", $raw, 2)[1] ?? '');
        $this->assertStringContainsString('Prehľad návštevnosti', $decoded);
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
