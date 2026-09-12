<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

/**
 * Maps upload surfaces to policy profiles (grep-verifiable in iteration gate).
 */
final class UploadSurfaceRegistry
{
    public const SURFACE_MEDIA_UPLOAD = 'media.upload';

    public const SURFACE_MEDIA_VIDEO_UPLOAD = 'media.video.upload';

    public const SURFACE_AVATAR_UPLOAD = 'avatar.upload';

    public const SURFACE_BACKUP_IMPORT = 'backup.import';

    public const SURFACE_EXTENSION_IMPORT = 'extension.import';

    public const SURFACE_THEME_IMPORT = 'theme.import';

    public const SURFACE_STOCK_IMPORT = 'stock.import';

    /**
     * @return array<string, string> surface id => profile id
     */
    public static function mappings(): array
    {
        return [
            self::SURFACE_MEDIA_UPLOAD => UploadPolicyProfileId::MEDIA,
            self::SURFACE_MEDIA_VIDEO_UPLOAD => UploadPolicyProfileId::MEDIA_VIDEO,
            self::SURFACE_AVATAR_UPLOAD => UploadPolicyProfileId::AVATAR,
            self::SURFACE_BACKUP_IMPORT => UploadPolicyProfileId::BACKUP_ARCHIVE,
            self::SURFACE_EXTENSION_IMPORT => UploadPolicyProfileId::EXTENSION_ARCHIVE,
            self::SURFACE_THEME_IMPORT => UploadPolicyProfileId::EXTENSION_ARCHIVE,
            self::SURFACE_STOCK_IMPORT => UploadPolicyProfileId::STOCK_IMPORT,
        ];
    }

    public static function profileForSurface(string $surfaceId): string
    {
        $profile = self::mappings()[$surfaceId] ?? '';

        if ($profile === '') {
            throw new UploadPolicyException('Neznámy upload povrch: ' . $surfaceId);
        }

        return $profile;
    }

    /**
     * @return list<string>
     */
    public static function surfaceIds(): array
    {
        return array_keys(self::mappings());
    }
}
