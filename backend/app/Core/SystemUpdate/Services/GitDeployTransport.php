<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use PaginiumCMS\Support\AppRoot;

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
        $ssh = self::gitSshCommandValue() ?? self::githubSshBaseCommand();
        exec($ssh . ' -T git@github.com 2>&1', $outputLines);
        $output = implode("\n", $outputLines);

        return str_contains($output, 'successfully authenticated')
            || preg_match('/\bHi [^\n!]+!/i', $output) === 1;
    }

    /**
     * Readable deploy-key path inside the PHP process.
     *
     * The host may store the key under /var/lib/paginiumcms/secrets while Compose
     * lives under /var/lib/docker/compose — those directories are unrelated.
     * What matters is a volume mount + a path that exists *inside* the container.
     * If the env var still points at the host path, fall back to the usual mounts.
     */
    public static function resolveDeploySshKeyPath(): ?string
    {
        $candidates = [
            trim((string) (getenv('GITHUB_DEPLOY_SSH_KEY_PATH') ?: ($_ENV['GITHUB_DEPLOY_SSH_KEY_PATH'] ?? ''))),
            '/run/secrets/github_deploy_key',
            '/var/lib/paginiumcms/secrets/github_deploy_key',
        ];

        foreach ($candidates as $path) {
            if ($path !== '' && self::isReadableKeyFile($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function isReadableKeyFile(string $path): bool
    {
        if (!str_starts_with($path, '/') || str_contains($path, "\0")) {
            return false;
        }

        return is_file($path) && is_readable($path);
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

        return 'HOME=/tmp GIT_SSH_COMMAND=' . escapeshellarg($command) . ' ';
    }

    public static function resolveGithubKnownHostsFile(): ?string
    {
        $candidates = [];
        $root = AppRoot::resolve();
        if ($root !== null) {
            $candidates[] = $root . '/docker/php/github_known_hosts';
        }
        $candidates[] = '/var/www/html/docker/php/github_known_hosts';

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function githubSshBaseCommand(): string
    {
        $knownHosts = self::resolveGithubKnownHostsFile();
        if ($knownHosts !== null) {
            return 'ssh -o BatchMode=yes -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile='
                . escapeshellarg($knownHosts);
        }

        return 'ssh -o BatchMode=yes -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new'
            . ' -o UserKnownHostsFile=/tmp/paginium-github-known_hosts';
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
        return self::prefersDeployKeySsh()
            || self::isGithubSshAuthAvailable()
            || self::hasUsableGithubDeployToken($config);
    }

    /**
     * Mounted deploy key + ssh binary — use git@ SSH and ignore a leftover PAT.
     */
    public static function prefersDeployKeySsh(): bool
    {
        return self::hasSshBinary() && self::hasDeploySshKeyConfigured();
    }
}
