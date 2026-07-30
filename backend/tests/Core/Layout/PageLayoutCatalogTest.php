<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\Layout\PageLayoutCatalog;
use PaginiumCMS\Core\Settings\SettingsSchema;

final class PageLayoutCatalogTest extends TestCase
{
    public function testTemplateCatalogHasAtLeastFiveEntries(): void
    {
        self::assertGreaterThanOrEqual(5, count(PageLayoutCatalog::TEMPLATES));
    }

    public function testNormalizeRejectsUnknownTemplate(): void
    {
        self::assertSame(PageLayoutCatalog::DEFAULT_TEMPLATE, PageLayoutCatalog::normalizeTemplate('not-a-template'));
        self::assertSame(PageLayoutCatalog::DEFAULT_TEMPLATE, PageLayoutCatalog::normalizeTemplate(''));
        self::assertSame(PageLayoutCatalog::DEFAULT_TEMPLATE, PageLayoutCatalog::normalizeTemplate(null));
        self::assertSame('landing', PageLayoutCatalog::normalizeTemplate('landing'));
    }

    public function testNormalizeRejectsUnknownBuilderMode(): void
    {
        self::assertSame(PageLayoutCatalog::DEFAULT_BUILDER_MODE, PageLayoutCatalog::normalizeBuilderMode('canvas'));
        self::assertSame('developer', PageLayoutCatalog::normalizeBuilderMode('developer'));
    }

    public function testLayoutSettingsDefaultsMatchCatalog(): void
    {
        $defaults = SettingsSchema::defaults()['layout'] ?? [];
        self::assertSame(PageLayoutCatalog::DEFAULT_BUILDER_MODE, $defaults['builderMode'] ?? null);
        self::assertSame(PageLayoutCatalog::DEFAULT_TEMPLATE, $defaults['defaultTemplate'] ?? null);
        self::assertTrue((bool) ($defaults['developerRequiresAdmin'] ?? false));
        self::assertTrue(PageLayoutCatalog::isValidTemplate((string) $defaults['defaultTemplate']));
        self::assertTrue(PageLayoutCatalog::isValidBuilderMode((string) $defaults['builderMode']));
    }
}
