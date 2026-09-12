<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

/**
 * Named upload policy profiles (It.78).
 */
final class UploadPolicyProfileId
{
    public const MEDIA = 'media';

    public const AVATAR = 'avatar';

    public const BACKUP_ARCHIVE = 'backup-archive';

    public const EXTENSION_ARCHIVE = 'extension-archive';

    public const STOCK_IMPORT = 'stock-import';

    /** Placeholder for It.79 — extends {@see self::MEDIA} constraints. */
    public const MEDIA_VIDEO = 'media-video';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MEDIA,
            self::AVATAR,
            self::BACKUP_ARCHIVE,
            self::EXTENSION_ARCHIVE,
            self::STOCK_IMPORT,
            self::MEDIA_VIDEO,
        ];
    }
}
