<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Canonical CMS semver for extension compatibility checks (Wave 5d).
 */
final class AppVersion
{
    public const VERSION = '2.1.0-beta.25';

    public static function current(): string
    {
        return self::VERSION;
    }
}
