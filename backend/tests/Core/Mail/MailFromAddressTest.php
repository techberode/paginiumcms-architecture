<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PaginiumCMS\Core\Mail\Services\MailFromAddress;
use PHPUnit\Framework\TestCase;

final class MailFromAddressTest extends TestCase
{
    public function testParseAngleAddr(): void
    {
        $this->assertSame('team@example.com', MailFromAddress::parse('Team <team@example.com>'));
    }
}
