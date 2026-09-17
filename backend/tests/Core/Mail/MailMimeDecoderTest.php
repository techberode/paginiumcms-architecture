<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Mail\Services\MailMimeDecoder;

final class MailMimeDecoderTest extends TestCase
{
    public function testDecodesRfc2047SubjectAndFrom(): void
    {
        $subject = '=?UTF-8?B?' . base64_encode('Log Error: ntfy access') . '?=';
        $from = '=?UTF-8?B?' . base64_encode('PaginiumCMS') . '?= <noreply@paginiumcms.com>';

        $this->assertSame('Log Error: ntfy access', MailMimeDecoder::header($subject));
        $this->assertSame('PaginiumCMS <noreply@paginiumcms.com>', MailMimeDecoder::header($from));
    }

    public function testJoinsFoldedEncodedWords(): void
    {
        $value = "=?UTF-8?B?" . base64_encode('Paginium') . "?=\n =?UTF-8?B?" . base64_encode('CMS') . "?=";
        $this->assertSame('PaginiumCMS', MailMimeDecoder::header($value));
    }

    public function testHtmlEmailBecomesReadableText(): void
    {
        $html = '<!DOCTYPE html><html lang="sk"><head><style>td{color:red}</style>'
            . '<title>PaginiumCMS — Monitoring</title></head><body>'
            . '<h1>PaginiumCMS</h1><p>Prehľad návštevnosti</p>'
            . '<table><tr><td>Dohodné</td><td>3</td></tr></table></body></html>';

        $text = MailMimeDecoder::body($html);
        $this->assertStringContainsString('PaginiumCMS', $text);
        $this->assertStringContainsString('Prehľad návštevnosti', $text);
        $this->assertStringContainsString('Dohodné', $text);
        $this->assertStringNotContainsString('<html', $text);
        $this->assertStringNotContainsString('td{color', $text);
    }

    public function testPresentExtractsEnvelopeHeadersFromMime(): void
    {
        $raw = "From: Sender <sender@example.com>\r\n"
            . "To: Guest <guest@example.com>, Other <other@example.com>\r\n"
            . "Cc: Copy <copy@example.com>\r\n"
            . "Reply-To: Help <help@example.com>\r\n"
            . "Subject: Hello\r\n"
            . "Date: Thu, 17 Sep 2026 10:00:00 +0200\r\n"
            . "\r\n"
            . "Body text";

        $row = MailMimeDecoder::present([
            'uid' => 3,
            'subject' => 'Hello',
            'from' => 'Sender <sender@example.com>',
            'date' => 'Thu, 17 Sep 2026 10:00:00 +0200',
            'mime' => $raw,
            'tags' => [],
            'flags' => [],
        ], true);

        $this->assertSame('Guest <guest@example.com>, Other <other@example.com>', $row['to']);
        $this->assertSame('Copy <copy@example.com>', $row['cc']);
        $this->assertSame('Help <help@example.com>', $row['replyTo']);
    }

    public function testPrefersPlainPartInMultipart(): void
    {
        $raw = "Content-Type: multipart/alternative; boundary=bound\n\n"
            . "--bound\nContent-Type: text/plain; charset=UTF-8\n\nPlain hello\n"
            . "--bound\nContent-Type: text/html; charset=UTF-8\n\n<html><p>Html hello</p></html>\n"
            . "--bound--";

        $this->assertSame('Plain hello', MailMimeDecoder::body($raw));
    }

    public function testMultipartRelatedEmbedsInlineCidImages(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $this->assertNotFalse($png);
        $b64 = chunk_split(base64_encode($png), 76, "\n");

        $raw = "Content-Type: multipart/related; boundary=outer\n\n"
            . "--outer\nContent-Type: multipart/alternative; boundary=inner\n\n"
            . "--inner\nContent-Type: text/plain; charset=UTF-8\n\nReply text\n"
            . "--inner\nContent-Type: text/html; charset=UTF-8\n\n"
            . '<html><body><p>Reply text</p><img src="cid:paginium-signature-avatar" alt=""></body></html>' . "\n"
            . "--inner--\n"
            . "--outer\nContent-Type: image/png\nContent-Transfer-Encoding: base64\nContent-ID: <paginium-signature-avatar>\n\n"
            . trim($b64) . "\n"
            . "--outer--";

        $row = MailMimeDecoder::present([
            'uid' => 9,
            'subject' => 'Re: test',
            'from' => 'guest@gmail.com',
            'date' => 'Thu, 17 Sep 2026 09:19:36 +0200',
            'mime' => $raw,
            'tags' => [],
            'flags' => [],
        ], true);

        $this->assertArrayHasKey('html', $row);
        $this->assertStringContainsString('Reply text', (string) $row['html']);
        $this->assertStringContainsString('data:image/png;base64,', (string) $row['html']);
        $this->assertStringNotContainsString('iVBORw0KGgo', (string) $row['body']);
        $this->assertSame('Reply text', $row['body']);
    }

    public function testDeliveryStatusBounceShowsHumanPlainPart(): void
    {
        $raw = "Content-Type: multipart/report; report-type=delivery-status; boundary=bound\n\n"
            . "--bound\nContent-Type: text/plain; charset=UTF-8\n\n"
            . "This is the mail system at host mail.webland.fun.\n\n"
            . "I'm sorry to have to inform you that your message could not be delivered.\n"
            . "<user@example.com>: host said: 550 Mailbox unavailable\n"
            . "--bound\nContent-Type: message/delivery-status\n\n"
            . "Reporting-MTA: dns; mail.webland.fun\n"
            . "X-Postfix-Queue-ID: 886C7DCD426\n"
            . "X-Postfix-Sender: rfc822; admin@paginiumcms.com\n"
            . "--bound--";

        $row = MailMimeDecoder::present([
            'uid' => 4,
            'subject' => 'Undelivered Mail Returned to Sender',
            'from' => 'MAILER-DAEMON@mail.webland.fun',
            'date' => 'Thu, 17 Sep 2026 09:11:08 +0200',
            'mime' => $raw,
            'tags' => [],
            'flags' => [],
        ], true);

        $this->assertArrayHasKey('html', $row);
        $this->assertStringContainsString('could not be delivered', (string) $row['html']);
        $this->assertStringContainsString('550 Mailbox unavailable', (string) $row['body']);
        $this->assertStringContainsString('Reporting-MTA', (string) $row['body']);
    }

    public function testPresentIgnoresMisassignedBase64HtmlPart(): void
    {
        $pngB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $raw = "Content-Type: multipart/alternative; boundary=bound\n\n"
            . "--bound\nContent-Type: text/plain; charset=UTF-8\n\nVisible plain\n"
            . "--bound\nContent-Type: text/html; charset=UTF-8\n\n<html><p>Visible html</p></html>\n"
            . "--bound--";

        $row = MailMimeDecoder::present([
            'uid' => 3,
            'subject' => 'Re: test',
            'from' => 'guest@gmail.com',
            'date' => 'Thu, 17 Sep 2026 09:19:36 +0200',
            'body' => $raw,
            'html' => $pngB64,
            'tags' => [],
            'flags' => [],
        ], true);

        $this->assertArrayHasKey('html', $row);
        $this->assertStringContainsString('Visible html', (string) $row['html']);
        $this->assertStringNotContainsString($pngB64, (string) $row['body']);
    }

    public function testHtmlDocumentKeepsLayoutAndDropsScripts(): void
    {
        $html = '<html><head><style>.box{color:#0ff}</style></head><body>'
            . '<div class="box" style="background:#020617">PaginiumCMS</div>'
            . '<script>alert(1)</script>'
            . '<a href="javascript:alert(1)">x</a>'
            . '</body></html>';

        $out = MailMimeDecoder::htmlDocument($html);
        $this->assertStringContainsString('PaginiumCMS', $out);
        $this->assertStringContainsString('.box{color:#0ff}', $out);
        $this->assertStringContainsString('background:#020617', $out);
        $this->assertStringNotContainsString('<script', strtolower($out));
        $this->assertStringNotContainsString('javascript:alert', strtolower($out));
    }

    public function testPresentIncludesSanitizedHtml(): void
    {
        $row = MailMimeDecoder::present([
            'uid' => 2,
            'subject' => 'Report',
            'from' => 'noreply@paginium.test',
            'date' => '2026-09-15',
            'body' => '<h1>Prehľad návštevnosti</h1><p>5</p>',
            'tags' => [],
            'flags' => [],
        ], true);

        $this->assertArrayHasKey('html', $row);
        $this->assertIsString($row['html']);
        $this->assertStringContainsString('Prehľad návštevnosti', $row['html']);
        $this->assertStringNotContainsString('&#318;', $row['html']);
        $this->assertStringNotContainsString('&aacute;', $row['html']);
        $this->assertIsString($row['body']);
        $this->assertStringContainsString('Prehľad návštevnosti', $row['body']);
    }

    public function testSanitizedHtmlKeepsEscapedAngleBrackets(): void
    {
        $out = MailMimeDecoder::htmlDocument('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');
        $this->assertStringContainsString('&lt;script&gt;', $out);
        $this->assertStringNotContainsString('<script', strtolower($out));
    }

    public function testPresentDecodesListRow(): void
    {
        $row = MailMimeDecoder::present([
            'uid' => 1,
            'subject' => '=?UTF-8?Q?Ahoj?=',
            'from' => '=?UTF-8?B?' . base64_encode('Kuchár') . '?=',
            'date' => 'Mon, 14 Sep 2026 08:00:01 +0200',
            'snippet' => '=?UTF-8?Q?Ahoj?=',
            'body' => '<p>secret</p>',
            'tags' => [],
            'flags' => [],
        ], false);

        $this->assertSame('Ahoj', $row['subject']);
        $this->assertSame('Kuchár', $row['from']);
        $this->assertArrayNotHasKey('body', $row);
        $this->assertArrayNotHasKey('html', $row);
    }
}
