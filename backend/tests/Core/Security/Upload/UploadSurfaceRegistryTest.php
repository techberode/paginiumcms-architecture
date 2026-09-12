<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Upload;

use PaginiumCMS\Core\Security\Upload\UploadPolicyProfileId;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PHPUnit\Framework\TestCase;

final class UploadSurfaceRegistryTest extends TestCase
{
    public function testEveryInventorySurfaceMapsToProfile(): void
    {
        $expected = [
            UploadSurfaceRegistry::SURFACE_MEDIA_UPLOAD => UploadPolicyProfileId::MEDIA,
            UploadSurfaceRegistry::SURFACE_AVATAR_UPLOAD => UploadPolicyProfileId::AVATAR,
            UploadSurfaceRegistry::SURFACE_BACKUP_IMPORT => UploadPolicyProfileId::BACKUP_ARCHIVE,
            UploadSurfaceRegistry::SURFACE_EXTENSION_IMPORT => UploadPolicyProfileId::EXTENSION_ARCHIVE,
            UploadSurfaceRegistry::SURFACE_THEME_IMPORT => UploadPolicyProfileId::EXTENSION_ARCHIVE,
            UploadSurfaceRegistry::SURFACE_STOCK_IMPORT => UploadPolicyProfileId::STOCK_IMPORT,
        ];

        $this->assertSame($expected, UploadSurfaceRegistry::mappings());
    }
}
