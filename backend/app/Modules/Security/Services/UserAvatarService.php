<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Content\AvatarImageProcessor;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\MediaFormats;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Assigns profile avatars via media storage (It.19c).
 */
final class UserAvatarService
{
    public function __construct(
        private MediaRepositoryInterface $media,
        private AvatarImageProcessor $avatarImages,
    ) {
    }

    /**
     * @throws FlatFileException
     */
    public function assignFromUpload(
        User $user,
        string $originalName,
        string $binary,
        string $mimeType
    ): string {
        $processed = $this->avatarImages->process($binary, $mimeType);
        $binary = $processed['binary'];
        $mimeType = $processed['mimeType'];

        MediaFormats::validate(
            'avatar.' . $processed['extension'],
            $binary,
            $mimeType,
            AvatarImageProcessor::ALLOWED_MIMES,
            true
        );

        $folder = 'avatars/' . $user->getId();
        $media = $this->media->saveUpload(
            'avatar.' . $processed['extension'],
            $binary,
            $mimeType,
            $user->getName(),
            $folder
        );

        return $media->getUrl();
    }

    /**
     * Assign avatar from an existing media-library URL (Settings pickers, media modal).
     *
     * @throws FlatFileException
     */
    public function assignFromMediaUrl(string $url): string
    {
        $path = $this->resolveMediaStoragePath($url);
        if ($path === null) {
            throw new FlatFileException('Neplatná URL média pre avatar');
        }

        $media = $this->media->findByPath($path);
        if ($media === null) {
            throw new FlatFileException('Médium neexistuje v knižnici');
        }

        $mimeType = strtolower($media->getMimeType());
        if (!in_array($mimeType, AvatarImageProcessor::ALLOWED_MIMES, true)) {
            throw new FlatFileException('Avatar musí byť JPEG, PNG alebo WebP.');
        }

        return $media->getUrl();
    }

    public function remove(User $user): void
    {
        $user->setAvatarUrl(null);
    }

    private function resolveMediaStoragePath(string $url): ?string
    {
        $raw = trim($url);
        if ($raw === '') {
            return null;
        }

        $path = parse_url($raw, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $raw;
        }

        $storagePrefix = '/storage/app/content/';
        if (str_starts_with($path, $storagePrefix)) {
            $relative = ltrim(substr($path, strlen($storagePrefix)), '/');
        } elseif (str_starts_with($path, '/api/media/file/')) {
            $relative = urldecode(substr($path, strlen('/api/media/file/')));
        } elseif (str_starts_with($path, '/media/')) {
            $relative = ltrim(substr($path, 1), '/');
        } elseif (str_starts_with($path, 'media/')) {
            $relative = $path;
        } else {
            return null;
        }

        if ($relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
            return null;
        }

        return $relative;
    }
}
