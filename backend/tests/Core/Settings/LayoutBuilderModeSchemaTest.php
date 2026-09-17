<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Settings\SettingsSchema;

final class LayoutBuilderModeSchemaTest extends TestCase
{
    public function testBuilderModeHelpDescribesLiveModes(): void
    {
        $fields = [];
        foreach (SettingsSchema::groups()['layout']['fields'] as $field) {
            $fields[$field['key']] = $field;
        }

        $help = (string) ($fields['builderMode']['help'] ?? '');
        self::assertStringNotContainsString('ďalších slice', $help);
        self::assertStringContainsString('outline', $help);
        self::assertStringContainsString('stránky', $help);
    }
}
