<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Settings\SettingsSchema;

final class GallerySettingsSchemaTest extends TestCase
{
    public function testGalleryDefaultsIncludePhase2Keys(): void
    {
        $defaults = SettingsSchema::defaults()['gallery'] ?? [];

        self::assertFalse((bool) ($defaults['enabled'] ?? true));
        self::assertSame('grid', $defaults['layout'] ?? null);
        self::assertSame('subtle', $defaults['effectPreset'] ?? null);
        self::assertTrue((bool) ($defaults['autoplayEnabled'] ?? false));
        self::assertSame(6000, (int) ($defaults['autoplayIntervalMs'] ?? 0));
        self::assertSame('below', $defaults['modalCaptionStyle'] ?? null);
        self::assertSame('/features', $defaults['publicRoute'] ?? null);
    }
}
