<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Support;

use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;

/**
 * Fail-closed plan / phase / item identifiers (It.87e).
 *
 * Files are written only as data/project-plans/{id}.json — the id must never
 * contain path separators, dots, or uppercase letters.
 */
final class ProjectPlanId
{
    public const PATTERN = '/^[a-z0-9-]+$/';
    public const MAX_LENGTH = 64;

    public static function isValid(string $id): bool
    {
        if ($id === '' || strlen($id) > self::MAX_LENGTH) {
            return false;
        }

        if (str_contains($id, '..') || str_contains($id, '/') || str_contains($id, '\\') || str_contains($id, "\0")) {
            return false;
        }

        return preg_match(self::PATTERN, $id) === 1;
    }

    /**
     * @throws InvalidPathException
     */
    public static function assertValid(string $id, string $field = 'id'): string
    {
        if (!self::isValid($id)) {
            throw new InvalidPathException($id, 'Invalid project-plan ' . $field);
        }

        return $id;
    }
}
