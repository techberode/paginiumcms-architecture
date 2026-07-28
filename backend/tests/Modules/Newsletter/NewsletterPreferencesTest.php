<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Newsletter;

use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PHPUnit\Framework\TestCase;

final class NewsletterPreferencesTest extends TestCase
{
    public function testParseEnabledListUsesDefaultsWhenEmpty(): void
    {
        $this->assertSame(
            NewsletterPreferences::DEFAULT_ENABLED,
            NewsletterPreferences::parseEnabledList('')
        );
    }

    public function testNormalizeSelectionFiltersUnknownKeys(): void
    {
        $selected = NewsletterPreferences::normalizeSelection(
            ['weekly_digest', 'invalid', 'general_news'],
            NewsletterPreferences::DEFAULT_ENABLED
        );

        $this->assertSame(['weekly_digest', 'general_news'], $selected);
    }
}
