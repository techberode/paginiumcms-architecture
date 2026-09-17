<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use InvalidArgumentException;
use PaginiumCMS\Core\Mail\Services\MailRecipientParser;
use PHPUnit\Framework\TestCase;

final class MailRecipientParserTest extends TestCase
{
    public function testParsesCommaSeparatedList(): void
    {
        $this->assertSame(
            ['a@example.com', 'b@example.com'],
            MailRecipientParser::parse('a@example.com, b@example.com')
        );
    }

    public function testParsesAngledAddresses(): void
    {
        $this->assertSame(
            ['guest@example.com', 'info@site.test'],
            MailRecipientParser::parse('Guest <guest@example.com>; info@site.test')
        );
    }

    public function testRejectsInvalidSegment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MailRecipientParser::parse('valid@example.com, not-an-email');
    }

    public function testFormatsToHeader(): void
    {
        $this->assertSame(
            '<a@example.com>, <b@example.com>',
            MailRecipientParser::formatToHeader(['a@example.com', 'b@example.com'])
        );
    }

    public function testNormalizesDraftRecipientsLeniently(): void
    {
        $this->assertSame(
            'a@example.com',
            MailRecipientParser::normalizeDraftRecipients('a@example.com, not-finished@')
        );
        $this->assertSame('wip@typing', MailRecipientParser::normalizeDraftRecipients('wip@typing'));
    }
}
