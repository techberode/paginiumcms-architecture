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
        exec(
            self::shellEnvPrefixForGit() . self::githubSshBaseCommand() . ' -T git@github.com 2>&1',
            $outputLines
        );
        $output = implode("\n", $outputLines);

        return str_contains($output, 'successfully authenticated')
            || preg_match('/\bHi [^\n!]+!/i', $output) === 1;
    }

    /**
     * Host path to a read-only GitHub deploy private key (mounted into the PHP container).
     */
    public static function resolveDeploySshKeyPath(): ?string
    {
        $path = trim((string) (getenv('GITHUB_DEPLOY_SSH_KEY_PATH') ?: ($_ENV['GITHUB_DEPLOY_SSH_KEY_PATH'] ?? '')));
        if ($path === '' || !is_readable($path)) {
            return null;
        }

        return $path;
    }

    public static function hasDeploySshKeyConfigured(): bool
    {
        return self::resolveDeploySshKeyPath() !== null;
    }

    public static function gitSshCommandValue(): ?string
    {
        $key = self::resolveDeploySshKeyPath();
        if ($key === null) {
            return null;
        }

        return self::githubSshBaseCommand() . ' -i ' . escapeshellarg($key);
    }

    /**
     * Prefix for exec() so git/ssh use the deploy key (GIT_SSH_COMMAND).
     */
    public static function shellEnvPrefixForGit(): string
    {
        $command = self::gitSshCommandValue();
        if ($command === null) {
            return '';
        }

        return 'GIT_SSH_COMMAND=' . escapeshellarg($command) . ' ';
    }

    private static function githubSshBaseCommand(): string
    {
        return 'ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new -o IdentitiesOnly=yes';
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

    /**
     * @param array<string, mixed> $config
     */
    public static function hasUsableGitTransport(array $config): bool
    {
        return self::isGithubSshAuthAvailable() || self::hasUsableGithubDeployToken($config);
    }
}
