<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Jednoduchý prekladač pre HTTP vrstvu.
 */
class Lang
{
    private static string $locale = 'sk';

    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    /**
     * @param array<string, string> $replace
     */
    public static function get(string $key, array $replace = [], ?string $group = null): string
    {
        [$groupName, $itemKey] = self::parseKey($key, $group);
        $messages = self::loadGroup($groupName);
        $message = $messages[$itemKey] ?? $key;

        foreach ($replace as $search => $value) {
            $message = str_replace(':' . $search, (string) $value, $message);
        }

        return $message;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function parseKey(string $key, ?string $group): array
    {
        if ($group !== null) {
            return [$group, $key];
        }

        if (str_contains($key, '.')) {
            $parts = explode('.', $key, 2);
            return [$parts[0], $parts[1]];
        }

        return ['content', $key];
    }

    /**
     * @return array<string, string>
     */
    private static function loadGroup(string $group): array
    {
        $cacheKey = self::$locale . '.' . $group;

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $path = __DIR__ . '/../../lang/' . self::$locale . '/' . $group . '.php';
        if (!file_exists($path)) {
            $fallback = __DIR__ . '/../../lang/sk/' . $group . '.php';
            $path = file_exists($fallback) ? $fallback : $path;
        }

        $messages = file_exists($path) ? require $path : [];
        self::$cache[$cacheKey] = is_array($messages) ? $messages : [];

        return self::$cache[$cacheKey];
    }
}
