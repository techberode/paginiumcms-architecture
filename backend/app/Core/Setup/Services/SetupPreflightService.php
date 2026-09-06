<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Setup\Services;

use PaginiumCMS\Core\Setup\Models\SetupPreflightCheck;
use PaginiumCMS\Core\Setup\Models\SetupPreflightSeverity;
use PaginiumCMS\Core\Setup\Models\SetupPreflightStatus;
use PaginiumCMS\Support\AppRoot;

/**
 * Read-only server prerequisite checks for the setup wizard (It.25 M1+).
 * No shell execution — only PHP introspection and filesystem probes.
 */
final class SetupPreflightService
{
    private const PHP_MIN_VERSION = '8.5.0';

    /** @var list<string> */
    private const REQUIRED_EXTENSIONS = ['json', 'mbstring', 'zip', 'curl', 'fileinfo'];

    /** @var list<string> */
    private const RECOMMENDED_EXTENSIONS = ['gd'];

    public function __construct(
        private string $storagePath,
        private ?string $projectRoot = null,
    ) {
        $this->storagePath = rtrim($storagePath, '/');
    }

    /**
     * @return array{ready: bool, hardBlockers: int, softWarnings: int, checks: list<array<string, mixed>>}
     */
    public function run(): array
    {
        $checks = [
            $this->checkPhpVersion(),
            ...$this->checkRequiredExtensions(),
            ...$this->checkRecommendedExtensions(),
            ...$this->checkStorageWritable(),
            $this->checkVendorAutoload(),
            $this->checkGitCli(),
            $this->checkComposerCli(),
            $this->checkDockerRuntime(),
        ];

        $hardBlockers = 0;
        $softWarnings = 0;

        foreach ($checks as $check) {
            if ($check->severity === SetupPreflightSeverity::Hard && $check->status === SetupPreflightStatus::Fail) {
                $hardBlockers++;
            }
            if ($check->severity === SetupPreflightSeverity::Soft && $check->status === SetupPreflightStatus::Warn) {
                $softWarnings++;
            }
        }

        return [
            'ready' => $hardBlockers === 0,
            'hardBlockers' => $hardBlockers,
            'softWarnings' => $softWarnings,
            'checks' => array_map(static fn (SetupPreflightCheck $check): array => $check->toArray(), $checks),
        ];
    }

    private function checkPhpVersion(): SetupPreflightCheck
    {
        $current = PHP_VERSION;
        $ok = version_compare($current, self::PHP_MIN_VERSION, '>=');

        return new SetupPreflightCheck(
            id: 'php_version',
            status: $ok ? SetupPreflightStatus::Pass : SetupPreflightStatus::Fail,
            severity: SetupPreflightSeverity::Hard,
            current: $current,
            required: '>= ' . self::PHP_MIN_VERSION,
            installSteps: $ok ? [] : $this->debianInstallSteps([
                'sudo apt update',
                'sudo apt install -y php8.5-cli php8.5-fpm php8.5-common',
                'php -v',
            ]),
        );
    }

    /**
     * @return list<SetupPreflightCheck>
     */
    private function checkRequiredExtensions(): array
    {
        $checks = [];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = new SetupPreflightCheck(
                id: 'php_ext_' . $extension,
                status: $loaded ? SetupPreflightStatus::Pass : SetupPreflightStatus::Fail,
                severity: SetupPreflightSeverity::Hard,
                current: $loaded ? 'loaded' : 'missing',
                required: 'loaded',
                installSteps: $loaded ? [] : $this->extensionInstallSteps($extension),
            );
        }

        return $checks;
    }

    /**
     * @return list<SetupPreflightCheck>
     */
    private function checkRecommendedExtensions(): array
    {
        $checks = [];

        foreach (self::RECOMMENDED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = new SetupPreflightCheck(
                id: 'php_ext_' . $extension,
                status: $loaded ? SetupPreflightStatus::Pass : SetupPreflightStatus::Warn,
                severity: SetupPreflightSeverity::Soft,
                current: $loaded ? 'loaded' : 'missing',
                required: 'recommended',
                installSteps: $loaded ? [] : $this->extensionInstallSteps($extension),
            );
        }

        return $checks;
    }

    /**
     * @return list<SetupPreflightCheck>
     */
    private function checkStorageWritable(): array
    {
        $paths = [
            'storage_root' => $this->storagePath,
            'storage_app' => $this->storagePath . '/app',
            'storage_logs' => $this->storagePath . '/logs',
            'storage_cache' => $this->storagePath . '/cache',
        ];

        $checks = [];

        foreach ($paths as $id => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $ok = $exists && $writable;

            $checks[] = new SetupPreflightCheck(
                id: $id,
                status: $ok ? SetupPreflightStatus::Pass : SetupPreflightStatus::Fail,
                severity: SetupPreflightSeverity::Hard,
                current: !$exists ? 'missing' : ($writable ? 'writable' : 'not writable'),
                required: 'writable directory',
                installSteps: $ok ? [] : $this->storageInstallSteps($path),
            );
        }

        return $checks;
    }

    private function checkVendorAutoload(): SetupPreflightCheck
    {
        $root = $this->resolvedProjectRoot();
        $vendorPath = $root !== null ? $root . '/vendor/autoload.php' : null;
        $exists = $vendorPath !== null && is_file($vendorPath);

        return new SetupPreflightCheck(
            id: 'composer_vendor',
            status: $exists ? SetupPreflightStatus::Pass : SetupPreflightStatus::Warn,
            severity: SetupPreflightSeverity::Soft,
            current: $exists ? 'present' : 'missing',
            required: 'vendor/autoload.php',
            installSteps: $exists ? [] : $this->debianInstallSteps([
                'sudo apt update',
                'sudo apt install -y composer',
                'cd ' . ($root ?? '/var/www/html'),
                'composer install --no-dev --optimize-autoloader',
            ]),
        );
    }

    private function checkGitCli(): SetupPreflightCheck
    {
        $path = $this->findExecutable('git');

        return new SetupPreflightCheck(
            id: 'cli_git',
            status: $path !== null ? SetupPreflightStatus::Pass : SetupPreflightStatus::Warn,
            severity: SetupPreflightSeverity::Soft,
            current: $path ?? 'not found',
            required: 'recommended for deploy',
            installSteps: $path !== null ? [] : $this->debianInstallSteps([
                'sudo apt update',
                'sudo apt install -y git',
                'git --version',
            ]),
        );
    }

    private function checkComposerCli(): SetupPreflightCheck
    {
        $path = $this->findExecutable('composer');

        return new SetupPreflightCheck(
            id: 'cli_composer',
            status: $path !== null ? SetupPreflightStatus::Pass : SetupPreflightStatus::Warn,
            severity: SetupPreflightSeverity::Soft,
            current: $path ?? 'not found',
            required: 'recommended for dependencies',
            installSteps: $path !== null ? [] : $this->debianInstallSteps([
                'sudo apt update',
                'sudo apt install -y composer',
                'composer --version',
            ]),
        );
    }

    private function checkDockerRuntime(): SetupPreflightCheck
    {
        $inContainer = is_file('/.dockerenv') || getenv('PAGINIUM_IN_DOCKER') === '1';
        $composeAvailable = $this->findExecutable('docker') !== null;

        if ($inContainer) {
            return new SetupPreflightCheck(
                id: 'runtime_docker',
                status: SetupPreflightStatus::Info,
                severity: SetupPreflightSeverity::Info,
                current: 'running inside container',
                required: 'optional',
                installSteps: [],
            );
        }

        return new SetupPreflightCheck(
            id: 'runtime_docker',
            status: $composeAvailable ? SetupPreflightStatus::Pass : SetupPreflightStatus::Info,
            severity: SetupPreflightSeverity::Info,
            current: $composeAvailable ? 'docker CLI available' : 'not detected',
            required: 'optional (Docker stack)',
            installSteps: $composeAvailable ? [] : $this->debianInstallSteps([
                'sudo apt update',
                'sudo apt install -y docker.io docker-compose-plugin',
                'sudo usermod -aG docker $USER',
                'newgrp docker',
            ]),
        );
    }

    /**
     * @param list<string> $commands
     * @return list<string>
     */
    private function debianInstallSteps(array $commands): array
    {
        return $commands;
    }

    /**
     * @return list<string>
     */
    private function extensionInstallSteps(string $extension): array
    {
        $package = match ($extension) {
            'mbstring' => 'php8.5-mbstring',
            'zip' => 'php8.5-zip',
            'curl' => 'php8.5-curl',
            'gd' => 'php8.5-gd',
            'fileinfo' => 'php8.5-common',
            'json' => 'php8.5-cli',
            default => 'php8.5-' . $extension,
        };

        return $this->debianInstallSteps([
            'sudo apt update',
            'sudo apt install -y ' . $package,
            'sudo systemctl restart php8.5-fpm  # when using PHP-FPM',
            'php -m | grep ' . $extension,
        ]);
    }

    /**
     * @return list<string>
     */
    private function storageInstallSteps(string $path): array
    {
        return $this->debianInstallSteps([
            'mkdir -p ' . $path,
            'chown -R www-data:www-data ' . $this->storagePath,
            'chmod -R u+rwX,g+rwX ' . $this->storagePath,
        ]);
    }

    private function resolvedProjectRoot(): ?string
    {
        return AppRoot::resolve($this->projectRoot);
    }

    private function findExecutable(string $name): ?string
    {
        /** @var list<string> $directories */
        $directories = [];

        $pathEnv = getenv('PATH');
        if (is_string($pathEnv) && $pathEnv !== '') {
            foreach (explode(':', $pathEnv) as $dir) {
                $dir = trim($dir);
                if ($dir !== '') {
                    $directories[] = $dir;
                }
            }
        }

        $directories[] = '/usr/local/bin';
        $directories[] = '/usr/bin';
        $directories[] = '/bin';

        $seen = [];
        foreach ($directories as $directory) {
            if (isset($seen[$directory])) {
                continue;
            }
            $seen[$directory] = true;

            $candidate = rtrim($directory, '/') . '/' . $name;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
