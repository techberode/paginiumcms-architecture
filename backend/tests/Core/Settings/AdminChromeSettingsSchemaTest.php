<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Settings\SettingsSchema;

final class AdminChromeSettingsSchemaTest extends TestCase
{
    public function testUiGroupIncludesAdminChromeDefaults(): void
    {
        $defaults = SettingsSchema::defaults()['ui'] ?? [];

        self::assertSame('default', $defaults['sidebarColor'] ?? null);
        self::assertSame('default', $defaults['topbarColor'] ?? null);
        self::assertFalse((bool) ($defaults['chromeGradient'] ?? true));
        self::assertSame('to-bottom', $defaults['chromeGradientDirection'] ?? null);
        self::assertSame('side', $defaults['navPlacement'] ?? null);
    }

    public function testUiChromeEnumsMatchPalette(): void
    {
        $fields = [];
        foreach (SettingsSchema::groups()['ui']['fields'] as $field) {
            $fields[$field['key']] = $field;
        }

        $colors = ['default', 'navy', 'slate', 'indigo', 'ocean', 'forest', 'wine', 'charcoal'];
        self::assertSame($colors, $fields['sidebarColor']['options'] ?? null);
        self::assertSame($colors, $fields['topbarColor']['options'] ?? null);
        self::assertSame(['side', 'top'], $fields['navPlacement']['options'] ?? null);
        self::assertSame(
            ['to-bottom', 'to-top', 'to-right', 'to-left', 'to-bottom-right', 'to-bottom-left'],
            $fields['chromeGradientDirection']['options'] ?? null
        );
    }
}
