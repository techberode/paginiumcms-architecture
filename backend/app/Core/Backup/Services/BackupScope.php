<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

/**
 * Allowed backup include flags and create/schedule modes.
 */
final class BackupScope
{
    public const MODE_FULL = 'full';
    public const MODE_INCREMENTAL = 'incremental';

    /** @var list<string> */
    public const ALLOWED_INCLUDES = [
        'content',
        'pages',
        'blog',
        'media',
        'data',
        'navigation',
        'trash',
        'config',
    ];

    /** @var list<string> */
    public const CONTENT_SUBTREES = ['pages', 'blog', 'media', 'data', 'navigation', 'trash'];

    /** @var list<string> */
    public const DEFAULT_INCLUDES = ['content', 'config'];

    /**
     * Filter to allowed flags. Empty when nothing valid was provided.
     *
     * @param array<int|string, mixed> $raw
     * @return list<string>
     */
    public static function sanitizeIncludes(array $raw): array
    {
        $selected = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }
            $flag = strtolower(trim($value));
            if (in_array($flag, self::ALLOWED_INCLUDES, true)) {
                $selected[$flag] = true;
            }
        }

        if (isset($selected['content'])) {
            foreach (self::CONTENT_SUBTREES as $subtree) {
                unset($selected[$subtree]);
            }
        }

        $ordered = [];
        foreach (self::ALLOWED_INCLUDES as $flag) {
            if (isset($selected[$flag])) {
                $ordered[] = $flag;
            }
        }

        return $ordered;
    }

    /**
     * @param array<int|string, mixed> $raw
     * @return list<string>
     */
    public static function normalizeIncludes(array $raw): array
    {
        $includes = self::sanitizeIncludes($raw);

        return $includes === [] ? self::DEFAULT_INCLUDES : $includes;
    }

    public static function normalizeMode(mixed $mode): string
    {
        if (!is_string($mode)) {
            return self::MODE_FULL;
        }

        $normalized = strtolower(trim($mode));

        return $normalized === self::MODE_INCREMENTAL ? self::MODE_INCREMENTAL : self::MODE_FULL;
    }

    /**
     * @param list<string> $includes
     */
    public static function includesSignature(array $includes): string
    {
        $normalized = self::normalizeIncludes($includes);
        sort($normalized);

        return implode(',', $normalized);
    }
}
