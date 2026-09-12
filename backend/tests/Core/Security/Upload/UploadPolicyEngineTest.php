<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Upload;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
use PaginiumCMS\Core\Security\Upload\UploadArchiveValidator;
use PaginiumCMS\Core\Security\Upload\UploadAuditLogger;
use PaginiumCMS\Core\Security\Upload\UploadFilenameGuard;
use PaginiumCMS\Core\Security\Upload\UploadMagicByteInspector;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadPolicyException;
use PaginiumCMS\Core\Security\Upload\UploadPolicyProfile;
use PaginiumCMS\Core\Security\Upload\UploadQuotaGuard;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class UploadPolicyEngineTest extends TestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function testRejectsDoubleExtensionOnMediaSurface(): void
    {
        $engine = $this->makeEngine();

        $this->expectException(UploadPolicyException::class);
        $engine->enforceBinary(
            UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD,
            'shell.php.jpg',
            $this->pngBytes(),
            'image/jpeg'
        );
    }

    public function testRejectsMimeMismatchOnMediaSurface(): void
    {
        $engine = $this->makeEngine();

        $this->expectException(UploadPolicyException::class);
        $engine->enforceBinary(
            UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD,
            'photo.png',
            $this->pngBytes(),
            'image/jpeg'
        );
    }

    public function testIntersectsSecurityAndMediaMimeLists(): void
    {
        $engine = $this->makeEngine([
            'uploadSecurity' => [
                'unifiedPolicyEnabled' => true,
                'allowedMimeTypes' => 'image/png,application/pdf',
                'maxUploadSizeKb' => 5120,
                'scanMagicBytes' => true,
                'blockDoubleExtensions' => true,
                'blockExecutables' => true,
                'allowedExtensions' => 'png,pdf',
            ],
            'media' => [
                'allowedMimeTypes' => 'image/png,image/jpeg,application/pdf',
                'maxUploadSizeKb' => 5120,
            ],
        ]);

        $allowed = $engine->resolveAllowedMimeTypes(
            UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD,
            ['image/png', 'image/jpeg', 'application/pdf']
        );

        $this->assertSame(['image/png', 'application/pdf'], $allowed);
    }

    public function testRejectsUnsafeZipEntryOnBackupImport(): void
    {
        $engine = $this->makeEngine();
        $zipPath = $this->createZip(['../evil.txt' => 'x']);

        $this->expectException(UploadPolicyException::class);
        $engine->enforceArchive(
            UploadSurfaceRegistry::SURFACE_BACKUP_IMPORT,
            'backup.zip',
            (int) filesize($zipPath),
            $zipPath
        );
    }

    public function testLegacyModeDoesNotRejectUnknownSurfaceWhenUnifiedDisabled(): void
    {
        $engine = $this->makeEngine([
            'uploadSecurity' => [
                'unifiedPolicyEnabled' => false,
            ],
        ]);

        $this->assertFalse($engine->isUnifiedEnabled());
        $engine->enforceArchive(
            UploadSurfaceRegistry::SURFACE_BACKUP_IMPORT,
            'backup.zip',
            10,
            sys_get_temp_dir() . '/missing.zip'
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, array<string, mixed>> $groups
     */
    private function makeEngine(array $groups = []): UploadPolicyEngine
    {
        $defaults = [
            'uploadSecurity' => [
                'unifiedPolicyEnabled' => true,
                'auditUploads' => false,
                'dailyQuotaBytesPerUser' => 0,
                'allowedMimeTypes' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
                'maxUploadSizeKb' => 5120,
                'backupImportMaxSizeKb' => 102400,
                'scanMagicBytes' => true,
                'blockDoubleExtensions' => true,
                'blockExecutables' => true,
                'allowedExtensions' => 'jpg,jpeg,png,gif,webp,svg,pdf,zip',
            ],
            'media' => [
                'allowedMimeTypes' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
                'maxUploadSizeKb' => 5120,
            ],
        ];

        $merged = array_replace_recursive($defaults, $groups);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(
            static fn (string $group): array => $merged[$group] ?? []
        );

        $root = sys_get_temp_dir() . '/pag_upload_policy_' . uniqid('', true);
        mkdir($root . '/content', 0777, true);
        $validator = new FileValidator($root . '/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        return new UploadPolicyEngine(
            $settings,
            new UploadPolicyProfile($settings),
            new UploadFilenameGuard($settings),
            new UploadMagicByteInspector(),
            new UploadArchiveValidator(new ZipEntryGuard()),
            new UploadQuotaGuard($settings, $reader, $writer),
            new UploadAuditLogger($settings, null)
        );
    }

    private function pngBytes(): string
    {
        $bytes = base64_decode(self::PNG_BASE64, true);
        $this->assertNotFalse($bytes);

        return $bytes;
    }

    /**
     * @param array<string, string> $entries
     */
    private function createZip(array $entries): string
    {
        $path = sys_get_temp_dir() . '/pag_test_' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        $this->addToAssertionCount(1);

        return $path;
    }
}
