<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

/**
 * Git transport detection for admin UI deploy (Docker PHP-FPM often has no ssh).
 */
final class GitDeployTransport
{
    public static function isSshAvailable(): bool
    {
        $path = getenv('PATH');
        $dirs = $path !== false && $path !== ''
            ? explode(':', $path)
            : ['/usr/local/bin', '/usr/bin', '/bin'];

        foreach ($dirs as $dir) {
            $dir = trim($dir);
            if ($dir === '') {
                continue;
            }
            $candidate = rtrim($dir, '/') . '/ssh';
            if (is_executable($candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $config systemUpdate settings group
     */
    public static function resolveGithubDeployToken(array $config): string
    {
        $fromSettings = trim((string) ($config['githubToken'] ?? ''));
        if ($fromSettings !== '' && $fromSettings !== '********') {
            return $fromSettings;
        }

        $fromEnv = trim((string) (getenv('GITHUB_DEPLOY_TOKEN') ?: ($_ENV['GITHUB_DEPLOY_TOKEN'] ?? '')));

        return $fromEnv;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function requiresHttpsToken(array $config): bool
    {
        return !self::isSshAvailable();
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function hasUsableGithubDeployToken(array $config): bool
    {
        return self::resolveGithubDeployToken($config) !== '';
    }
}
