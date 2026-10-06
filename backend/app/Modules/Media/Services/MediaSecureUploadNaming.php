<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Modules\Media\MediaFormats;

/**
 * Server-side storage filenames for media uploads (It.99).
 */
final class MediaSecureUploadNaming
{
    public static function build(
        string $mediaId,
        string $mimeType,
        string $legacySafeName,
        bool $secureNamingEnabled,
    ): string {
        if (!$secureNamingEnabled) {
            return $mediaId . '_' . $legacySafeName;
        }

        $mimeType = strtolower(trim($mimeType));
        $date = gmdate('Ymd');
        $shortId = self::shortIdFromMediaId($mediaId);

        if (MediaFormats::isVideoMime($mimeType)) {
            $extension = $mimeType === 'video/webm' ? 'webm' : 'mp4';

            return 'video_' . $date . '_' . $shortId . '.' . $extension;
        }

        if (str_starts_with($mimeType, 'image/')) {
            $extension = self::extensionForImageMime($mimeType, $legacySafeName);

            return 'image_' . $date . '_' . $shortId . '.' . $extension;
        }

        return $mediaId . '_' . $legacySafeName;
    }

    private static function shortIdFromMediaId(string $mediaId): string
    {
        $normalized = str_replace('media_', '', $mediaId);
        $normalized = preg_replace('/[^a-zA-Z0-9]/', '', $normalized) ?? $normalized;

        if ($normalized === '') {
            $normalized = bin2hex(random_bytes(4));
        }

        return substr($normalized, 0, 8);
    }

    private static function extensionForImageMime(string $mimeType, string $legacySafeName): string
    {
        $fromMime = match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => '',
        };

        if ($fromMime !== '') {
            return $fromMime;
        }

        $ext = strtolower(pathinfo($legacySafeName, PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : 'bin';
    }
}
