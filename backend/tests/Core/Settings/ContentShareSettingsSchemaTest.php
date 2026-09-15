<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Settings;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Settings\SettingsSchema;

final class ContentShareSettingsSchemaTest extends TestCase
{
    public function testContentGroupIncludesShareToggles(): void
    {
        $fields = [];
        foreach (SettingsSchema::groups()['content']['fields'] as $field) {
            $fields[$field['key']] = $field;
        }

        self::assertTrue((bool) ($fields['shareEnabled']['default'] ?? false));
        self::assertTrue((bool) ($fields['shareOnArticles']['default'] ?? false));
        self::assertFalse((bool) ($fields['shareOnPages']['default'] ?? true));
        self::assertTrue((bool) ($fields['shareFacebook']['default'] ?? false));
        self::assertTrue((bool) ($fields['shareX']['default'] ?? false));
    }
}
