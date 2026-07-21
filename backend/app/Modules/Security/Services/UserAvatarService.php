<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\MediaFormats;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Assigns profile avatars via media storage (It.19c).
 */
final class UserAvatarService
{
    /** @var list<string> */
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(
        private MediaRepositoryInterface $media
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
        $mimeType = strtolower(trim($mimeType));
        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new FlatFileException('Avatar musí byť obrázok (JPEG, PNG, WebP alebo GIF)');
        }

        MediaFormats::validate(
            $originalName,
            $binary,
            $mimeType,
            self::ALLOWED_MIMES,
            true
        );

        $folder = 'avatars/' . $user->getId();
        $media = $this->media->saveUpload($originalName, $binary, $mimeType, $user->getName(), $folder);

        return $media->getUrl();
    }

    public function remove(User $user): void
    {
        $user->setAvatarUrl(null);
    }
}
