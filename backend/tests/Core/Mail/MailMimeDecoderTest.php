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

    public function testPrefersPlainPartInMultipart(): void
    {
        $raw = "Content-Type: multipart/alternative; boundary=bound\n\n"
            . "--bound\nContent-Type: text/plain; charset=UTF-8\n\nPlain hello\n"
            . "--bound\nContent-Type: text/html; charset=UTF-8\n\n<html><p>Html hello</p></html>\n"
            . "--bound--";

        $this->assertSame('Plain hello', MailMimeDecoder::body($raw));
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
        $this->assertStringContainsString('Prehľad návštevnosti', $row['body']);
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
