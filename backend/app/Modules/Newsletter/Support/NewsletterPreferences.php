<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Support;

final class NewsletterPreferences
{
    public const WEEKLY_DIGEST = 'weekly_digest';
    public const NEW_ARTICLE = 'new_article';
    public const CMS_RELEASE = 'cms_release';
    public const GENERAL_NEWS = 'general_news';

    /** @var list<string> */
    public const ALL = [
        self::WEEKLY_DIGEST,
        self::NEW_ARTICLE,
        self::CMS_RELEASE,
        self::GENERAL_NEWS,
    ];

    /** @var list<string> */
    public const DEFAULT_ENABLED = [
        self::WEEKLY_DIGEST,
        self::GENERAL_NEWS,
    ];

    /**
     * Parse admin setting (one key per line or comma-separated).
     *
     * @return list<string>
     */
    public static function parseEnabledList(string $raw): array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return self::DEFAULT_ENABLED;
        }

        $parts = preg_split('/[\r\n,]+/', $trimmed) ?: [];
        $enabled = [];
        foreach ($parts as $part) {
            $key = trim($part);
            if ($key !== '' && in_array($key, self::ALL, true)) {
                $enabled[] = $key;
            }
        }

        return $enabled !== [] ? array_values(array_unique($enabled)) : self::DEFAULT_ENABLED;
    }

    /**
     * @param list<string> $requested
     * @param list<string> $enabledInSettings
     * @return list<string>
     */
    public static function normalizeSelection(array $requested, array $enabledInSettings): array
    {
        $allowed = array_values(array_intersect($enabledInSettings, self::ALL));
        if ($allowed === []) {
            $allowed = self::DEFAULT_ENABLED;
        }

        $normalized = [];
        foreach ($requested as $item) {
            $key = trim($item);
            if ($key !== '' && in_array($key, $allowed, true)) {
                $normalized[] = $key;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     * @return list<string>
     */
    public static function merge(array $left, array $right): array
    {
        return array_values(array_unique(array_merge($left, $right)));
    }
}
