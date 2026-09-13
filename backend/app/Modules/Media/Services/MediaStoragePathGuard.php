<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Validates relative media object keys before storage driver I/O (Iteration 72).
 */
final class MediaStoragePathGuard
{
    public static function assertSafeRelativePath(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            throw new FlatFileException('Empty media storage path');
        }

        if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            throw new FlatFileException('Invalid media storage path');
        }

        if (!preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_\\.-]*$#', $relativePath)) {
            throw new FlatFileException('Invalid media storage path');
        }

        return $relativePath;
    }
}
