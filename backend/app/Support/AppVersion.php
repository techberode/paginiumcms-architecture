<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Canonical CMS semver for extension compatibility checks (Wave 5d).
 *
 * Runtime prefers an annotated git tag from the checkout (e.g. v2.1.0-beta.32);
 * falls back to VERSION when .git is unavailable or describe is not semver-shaped.
 */
final class AppVersion
{
    /** Fallback when git tag cannot be resolved (e.g. exported tarball, CI without tags). */
    public const VERSION = '2.1.0-beta.46';

    private static ?string $resolved = null;

    public static function current(): string
    {
        if (self::$resolved === null) {
            self::$resolved = self::resolveFromGit() ?? self::VERSION;
        }

        return self::$resolved;
    }

    /**
     * Resets cached resolution (tests only).
     */
    public static function resetCacheForTesting(): void
    {
        self::$resolved = null;
    }

    private static function resolveFromGit(): ?string
    {
        $root = AppRoot::resolve();
        if ($root === null || !is_dir($root . '/.git')) {
            return null;
        }

        $command = 'git -C ' . escapeshellarg($root) . ' describe --tags --always 2>/dev/null';
        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !isset($output[0])) {
            return null;
        }

        $describe = trim($output[0]);
        if ($describe === '') {
            return null;
        }

        if (str_starts_with($describe, 'v')) {
            $describe = substr($describe, 1);
        }

        if (preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $describe) !== 1) {
            return null;
        }

        return $describe;
    }
}
