<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Jednoduchý prekladač pre HTTP vrstvu.
 * Jeden súbor na modul a locale: backend/lang/{locale}/{group}.php
 */
class Lang
{
    private static string $locale = 'sk';

    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    /** @var list<string> */
    private static array $extraPaths = [];

    /** @var list<string> */
    private static array $supportedLocales = ['sk', 'en'];

    /**
     * @param list<string> $locales
     */
    public static function setSupportedLocales(array $locales): void
    {
        $filtered = array_values(array_filter(
            array_map(static fn ($locale): string => strtolower(trim((string) $locale)), $locales),
            static fn (string $locale): bool => $locale !== ''
        ));

        if ($filtered !== []) {
            self::$supportedLocales = $filtered;
        }
    }

    public static function setLocale(string $locale): void
    {
        $locale = strtolower(trim($locale));
        self::$locale = in_array($locale, self::$supportedLocales, true) ? $locale : 'sk';
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    /**
     * Registruje dodatočný adresár pre jazykové súbory (pluginy/témy/doplnky).
     */
    public static function addPath(string $absolutePath): void
    {
        if (!in_array($absolutePath, self::$extraPaths, true)) {
            self::$extraPaths[] = $absolutePath;
            self::$cache = [];
        }
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

        $path = self::resolveGroupPath(self::$locale, $group);
        if (!file_exists($path)) {
            $path = self::resolveGroupPath('sk', $group);
        }

        $messages = file_exists($path) ? require $path : [];
        self::$cache[$cacheKey] = is_array($messages) ? $messages : [];

        return self::$cache[$cacheKey];
    }

    private static function resolveGroupPath(string $locale, string $group): string
    {
        foreach (array_reverse(self::$extraPaths) as $base) {
            $candidate = rtrim($base, '/') . '/' . $locale . '/' . $group . '.php';
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return __DIR__ . '/../../lang/' . $locale . '/' . $group . '.php';
    }

    /** Len pre testy – reset cache a extra paths. */
    public static function resetForTests(): void
    {
        self::$locale = 'sk';
        self::$cache = [];
        self::$extraPaths = [];
    }
}
