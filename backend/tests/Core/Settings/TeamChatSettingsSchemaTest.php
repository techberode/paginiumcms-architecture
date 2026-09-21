<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PaginiumCMS\Core\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

final class TeamChatSettingsSchemaTest extends TestCase
{
    public function testTeamChatGroupDefaults(): void
    {
        $defaults = SettingsSchema::defaults()['teamChat'] ?? [];
        $this->assertSame(30, $defaults['retentionDays'] ?? 0);
        $this->assertSame(800, $defaults['maxStoredMessages'] ?? 0);
        $this->assertSame(80, $defaults['liveWindowMessages'] ?? 0);
        $this->assertSame(100, $defaults['searchMaxResults'] ?? 0);
        $this->assertFalse(SettingsSchema::isSuperAdminOnly('teamChat'));
    }
}
