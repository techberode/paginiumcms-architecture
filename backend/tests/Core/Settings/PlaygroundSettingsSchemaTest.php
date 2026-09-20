<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PaginiumCMS\Core\Settings\SettingsSchema;
use PHPUnit\Framework\TestCase;

final class PlaygroundSettingsSchemaTest extends TestCase
{
    public function testPlaygroundGroupDefaultsAndGates(): void
    {
        $defaults = SettingsSchema::defaults()['playground'] ?? [];
        $this->assertFalse($defaults['enabled'] ?? true);
        $this->assertSame('react-ts', $defaults['template'] ?? '');
        $this->assertSame('paginium-starter', $defaults['enabledPacks'] ?? '');
        $this->assertTrue(SettingsSchema::isSuperAdminOnly('playground'));

        $keys = array_map(
            static fn (array $field): string => (string) $field['key'],
            SettingsSchema::groups()['playground']['fields']
        );
        $this->assertSame(['enabled', 'template', 'enabledPacks'], $keys);
    }
}
