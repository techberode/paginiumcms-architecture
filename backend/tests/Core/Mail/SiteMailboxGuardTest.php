<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use InvalidArgumentException;
use PaginiumCMS\Core\Mail\Services\SiteMailboxGuard;
use PHPUnit\Framework\TestCase;

final class SiteMailboxGuardTest extends TestCase
{
    public function testSiteHostStripsWww(): void
    {
        $this->assertSame('example.com', SiteMailboxGuard::siteHost('https://www.example.com/cms'));
    }

    public function testAcceptsMailboxOnApexWhenSiteIsSubdomain(): void
    {
        $this->expectNotToPerformAssertions();
        SiteMailboxGuard::assertMailbox('hello@webland.fun', SiteMailboxGuard::siteHost('https://mail.webland.fun'));
    }

    public function testIgnoresLanIpSiteUrlAndUsesCompanyWebsite(): void
    {
        $this->assertSame(
            'webland.fun',
            SiteMailboxGuard::siteHost('http://192.168.10.26:8081', 'https://webland.fun')
        );
    }

    public function testRejectsForeignMailbox(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SiteMailboxGuard::assertMailbox('user@gmail.com', 'example.com');
    }

    public function testAcceptsExactSiteMailbox(): void
    {
        $this->expectNotToPerformAssertions();
        SiteMailboxGuard::assertMailbox('hello@example.com', SiteMailboxGuard::siteHost('https://www.example.com'));
    }

    public function testFallsBackToImapHostWhenSiteUrlIsLanIp(): void
    {
        $host = SiteMailboxGuard::siteHost('http://192.168.10.26:8081', '', 'mail.webland.fun');
        $this->assertSame('mail.webland.fun', $host);
        SiteMailboxGuard::assertMailbox('hello@webland.fun', $host);
        $this->assertTrue(SiteMailboxGuard::isMailboxAllowed('hello@webland.fun', $host));
    }

    public function testSkipsPublicMailProviderAsSiteHost(): void
    {
        $this->assertSame('', SiteMailboxGuard::siteHost('imap.gmail.com'));
    }

    public function testRejectsGmailImapEvenInPrivateMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SiteMailboxGuard::assertImapHost('imap.gmail.com', 'example.com', true);
    }

    public function testRejectsForeignImapHost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SiteMailboxGuard::assertImapHost('imap.gmail.com', 'example.com', false);
    }

    public function testSpamFolderNames(): void
    {
        $this->assertTrue(SiteMailboxGuard::isSpamFolder('Junk'));
        $this->assertTrue(SiteMailboxGuard::isSpamFolder('Spam'));
        $this->assertFalse(SiteMailboxGuard::isSpamFolder('INBOX'));
    }
}
