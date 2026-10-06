<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Upload;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Security\Upload\PolyglotUploadGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PolyglotUploadGuardTest extends TestCase
{
    public function testRejectsJpegWithScriptMarkerInSampleWindow(): void
    {
        $bytes = "\xFF\xD8\xFF" . str_repeat('A', 50_000) . '<script>alert(1)</script>';
        $guard = $this->guard(['polyglotMarkerScanEnabled' => true]);

        $this->expectException(FlatFileException::class);
        $guard->assertClean($bytes, 'image/jpeg');
    }

    public function testRejectsSvgWithOnloadHandler(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="10" height="10"/></svg>';
        $guard = $this->guard();

        $this->expectException(FlatFileException::class);
        $guard->assertClean($svg, 'image/svg+xml');
    }

    public function testAllowsCleanPng(): void
    {
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $this->assertNotFalse($bytes);
        $guard = $this->guard();
        $guard->assertClean($bytes, 'image/png');
        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, mixed> $uploadSecurity
     */
    private function guard(array $uploadSecurity = []): PolyglotUploadGuard
    {
        $defaults = [
            'polyglotMarkerScanEnabled' => true,
            'polyglotMarkerScanMaxBytes' => 65536,
            'reencodeRasterUploads' => false,
            'secureMediaFileNaming' => true,
        ];

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(
            static fn (string $group): array => $group === 'uploadSecurity'
                ? array_replace($defaults, $uploadSecurity)
                : []
        );

        return new PolyglotUploadGuard($settings);
    }
}
