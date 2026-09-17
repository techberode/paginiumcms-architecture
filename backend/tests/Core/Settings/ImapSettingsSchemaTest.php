<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Settings\SettingsSchema;

final class ImapSettingsSchemaTest extends TestCase
{
    public function testImapGroupDefaultsAreFailClosed(): void
    {
        $defaults = SettingsSchema::defaults()['imap'] ?? [];
        self::assertFalse((bool) ($defaults['enabled'] ?? true));
        self::assertSame('', $defaults['host'] ?? 'missing');
        self::assertSame('', $defaults['allowedDomain'] ?? 'missing');
        self::assertSame(993, $defaults['port'] ?? null);
        self::assertSame('ssl', $defaults['encryption'] ?? null);
        self::assertSame('Junk', $defaults['spamFolder'] ?? null);
        self::assertSame(40, $defaults['listLimit'] ?? null);
        self::assertTrue((bool) ($defaults['appendSentOnSend'] ?? false));
    }
}
