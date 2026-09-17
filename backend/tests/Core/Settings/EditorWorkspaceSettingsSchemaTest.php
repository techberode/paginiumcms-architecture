<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Settings\SettingsSchema;

final class EditorWorkspaceSettingsSchemaTest extends TestCase
{
    public function testEditorDefaultsIncludeFullscreenWorkspace(): void
    {
        $defaults = SettingsSchema::defaults()['editor'] ?? [];

        self::assertArrayHasKey('fullscreenWorkspace', $defaults);
        self::assertFalse((bool) $defaults['fullscreenWorkspace']);
    }
}
