<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

/**
 * Bundled default blog author avatar (PNG in media/defaults).
 */
final class DefaultAuthorAvatar
{
    public const STORAGE_URL = '/storage/app/content/media/defaults/author-avatar.png';

    public const RELATIVE_PATH = 'media/defaults/author-avatar.png';

    public static function resourcePath(): string
    {
        return dirname(__DIR__, 3) . '/resources/defaults/author-avatar.png';
    }
}
