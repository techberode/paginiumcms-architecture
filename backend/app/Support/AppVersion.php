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
    public const VERSION = '2.1.0-beta.67';

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

    /**
     * Extract semver from `git describe` output (exact tag or `tag-N-gHASH`).
     */
    public static function semverFromDescribe(string $describe): ?string
    {
        $describe = trim($describe);
        if ($describe === '') {
            return null;
        }

        if (str_starts_with($describe, 'v') || str_starts_with($describe, 'V')) {
            $describe = substr($describe, 1);
        }

        if (preg_match('/^(\d+\.\d+\.\d+(?:-[a-zA-Z0-9.]+)?)/', $describe, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private static function resolveFromGit(): ?string
    {
        $root = AppRoot::resolve();
        if ($root === null || !is_dir($root . '/.git')) {
            return null;
        }

        $rootArg = escapeshellarg($root);
        $commands = [
            'git -C ' . $rootArg . ' describe --tags --exact-match 2>/dev/null',
            'git -C ' . $rootArg . ' describe --tags --abbrev=0 2>/dev/null',
            'git -C ' . $rootArg . ' describe --tags --always 2>/dev/null',
        ];

        foreach ($commands as $command) {
            $output = [];
            $exitCode = 1;
            exec($command, $output, $exitCode);
            if ($exitCode !== 0 || !isset($output[0])) {
                continue;
            }

            $semver = self::semverFromDescribe(trim($output[0]));
            if ($semver !== null) {
                return $semver;
            }
        }

        return null;
    }
}
