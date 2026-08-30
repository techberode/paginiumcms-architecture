<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Themes;

use PaginiumCMS\Http\Themes\Services\ThemeStarterPackageService;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ThemeStarterPackageServiceTest extends TestCase
{
    public function testBuildZipPathContainsThemeManifest(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $root = dirname(__DIR__, 3) . '/resources/theme-packages';
        $service = new ThemeStarterPackageService($root);
        $zipPath = $service->buildZipPath('clean-journal');

        try {
            $this->assertFileExists($zipPath);

            $zip = new ZipArchive();
            $this->assertTrue($zip->open($zipPath));
            $this->assertNotFalse($zip->locateName('clean-journal/theme.json'));
            $zip->close();
        } finally {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
        }
    }

    public function testBuildZipPathRejectsUnknownId(): void
    {
        $root = dirname(__DIR__, 3) . '/resources/theme-packages';
        $service = new ThemeStarterPackageService($root);

        $this->expectException(\RuntimeException::class);
        $service->buildZipPath('unknown-theme');
    }
}
