<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\I18n;

use PaginiumCMS\Core\I18n\Services\LocaleScaffoldService;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PHPUnit\Framework\TestCase;

final class SupportedLocalesRegistryTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = sys_get_temp_dir() . '/pag_locales_' . uniqid();
        mkdir($this->configDir . '/config/i18n', 0777, true);
    }

    protected function tearDown(): void
    {
        $file = $this->configDir . '/config/i18n/locales.json';
        if (is_file($file)) {
            unlink($file);
        }
        @rmdir($this->configDir . '/config/i18n');
        @rmdir($this->configDir . '/config');
        @rmdir($this->configDir);
    }

    public function testAddsCustomLocale(): void
    {
        $registry = new SupportedLocalesRegistry($this->configDir);
        $entry = $registry->add('de', 'Deutsch');

        $this->assertSame('de', $entry['code']);
        $this->assertTrue($registry->isSupported('de'));
        $this->assertContains('de', $registry->codes());
    }

    public function testLocalePatternIncludesCustomLocale(): void
    {
        $registry = new SupportedLocalesRegistry($this->configDir);
        $registry->add('de', 'Deutsch');

        $this->assertStringContainsString('de', $registry->localePattern());
        $this->assertStringContainsString('sk', $registry->localePattern());
    }

    public function testScaffoldRejectsDuplicateLocale(): void
    {
        $registry = new SupportedLocalesRegistry($this->configDir);
        $registry->add('de', 'Deutsch');
        $scaffold = new LocaleScaffoldService($registry, $this->configDir);

        $this->expectException(\RuntimeException::class);
        $scaffold->scaffold('de', 'sk');
    }
}
