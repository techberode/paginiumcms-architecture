<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use InvalidArgumentException;

/**
 * Validates public SPA page paths for analytics beacons.
 */
final class PageviewPathValidator
{
    private const MAX_LENGTH = 512;

    public static function assertValid(string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '') {
            throw new InvalidArgumentException('Page path is required');
        }

        if (!str_starts_with($uri, '/')) {
            throw new InvalidArgumentException('Page path must start with /');
        }

        if (strlen($uri) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Page path is too long');
        }

        if (str_contains($uri, '..') || str_contains($uri, "\0")) {
            throw new InvalidArgumentException('Invalid page path');
        }

        $lower = strtolower($uri);
        foreach (['/api/', '/admin/', '/assets/', '/storage/'] as $blocked) {
            if (str_starts_with($lower, $blocked)) {
                throw new InvalidArgumentException('Path is not trackable');
            }
        }

        return $uri;
    }
}
