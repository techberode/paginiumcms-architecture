<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

/**
 * Stable public URL contract for media binaries (Iteration 72).
 *
 * Content documents reference media IDs/paths; physical storage location may change during migration.
 */
final class MediaUrlResolver
{
    public function localPublicUrl(string $relativePath): string
    {
        return '/storage/app/content/' . ltrim($relativePath, '/');
    }

    public function s3PublicUrl(string $relativePath, S3MediaStorageConfig $config): string
    {
        if ($config->visibility === 'public' && $config->publicBaseUrl !== '') {
            return rtrim($config->publicBaseUrl, '/') . '/' . ltrim($relativePath, '/');
        }

        return $this->apiServeUrl($relativePath);
    }

    public function apiServeUrl(string $relativePath): string
    {
        return '/api/media/file/' . rawurlencode(ltrim($relativePath, '/'));
    }
}
