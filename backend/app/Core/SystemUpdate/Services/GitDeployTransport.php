<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

/**
 * Git transport detection for admin UI deploy (Docker PHP-FPM often has no ssh).
 */
final class GitDeployTransport
{
    /**
     * True when git@github.com can authenticate non-interactively (deploy key / agent).
     * PHP containers often ship the ssh binary without any key for www-data.
     */
    public static function isSshAvailable(): bool
    {
        return self::isGithubSshAuthAvailable();
    }

    public static function hasSshBinary(): bool
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

    public static function isGithubSshAuthAvailable(): bool
    {
        if (!self::hasSshBinary()) {
            return false;
        }

        $outputLines = [];
        $exitCode = 255;
        exec(
            'ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new -T git@github.com 2>&1',
            $outputLines,
            $exitCode
        );
        $output = implode("\n", $outputLines);

        return str_contains($output, 'successfully authenticated')
            || preg_match('/\bHi [^\n!]+!/i', $output) === 1;
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
