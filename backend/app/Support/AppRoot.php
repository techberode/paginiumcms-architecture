<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

/**
 * Resolves repository root for CLI/deploy (host path vs Docker /var/www/html).
 */
final class AppRoot
{
    /**
     * Repo root where scripts/ and backend/ live (usable from PHP inside Docker).
     */
    public static function resolve(?string $override = null): ?string
    {
        /** @var list<string> $candidates */
        $candidates = [];

        if ($override !== null && $override !== '') {
            $candidates[] = $override;
        }

        $env = getenv('APP_ROOT') ?: ($_ENV['APP_ROOT'] ?? '');
        if (is_string($env) && $env !== '') {
            $candidates[] = $env;
        }

        // Production Docker bind-mount (see docs/deploy/docker-compose.prod.yml).
        $candidates[] = '/var/www/html';

        $fromSupport = realpath(dirname(__DIR__, 3));
        if ($fromSupport !== false) {
            $candidates[] = $fromSupport;
        }

        $seen = [];
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '' || isset($seen[$candidate])) {
                continue;
            }
            $seen[$candidate] = true;

            $real = realpath($candidate);
            $root = $real !== false ? $real : $candidate;

            if (self::isRepoRoot($root)) {
                return $root;
            }
        }

        return null;
    }

    public static function isRepoRoot(string $path): bool
    {
        return is_file($path . '/scripts/deploy-instance-update.sh')
            || is_file($path . '/backend/bin/console');
    }
}
