<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

/**
 * Rejects Zip-Slip and absolute paths in archive entries.
 */
final class ZipEntryGuard
{
    public function isSafeEntry(string $entryName): bool
    {
        if ($entryName === '' || str_contains($entryName, "\0")) {
            return false;
        }

        $normalized = str_replace('\\', '/', $entryName);

        if (str_starts_with($normalized, '/') || preg_match('#^[a-zA-Z]:/#', $normalized) === 1) {
            return false;
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }
}
