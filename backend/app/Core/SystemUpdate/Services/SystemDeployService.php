<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Support\AppRoot;

/**
 * Whitelisted deploy runner — invokes scripts/deploy-instance-update.sh only (It.63).
 */
final class SystemDeployService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ?string $appRoot = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function deploy(array $payload = []): JobRunResult
    {
        if ($this->isTesting()) {
            return new JobRunResult(
                false,
                'Deploy skipped in testing environment',
                ['ref' => $payload['ref'] ?? null],
                'testing_skipped'
            );
        }

        if (DemoMode::isEnabledFromEnv()) {
            return new JobRunResult(false, 'Deploy disabled on demo instance', [], 'demo_disabled');
        }

        $config = $this->settings->group('systemUpdate');
        if (!(bool) ($config['deployEnabled'] ?? false)) {
            return new JobRunResult(false, 'System deploy is disabled in settings', [], 'disabled');
        }

        $ref = trim((string) ($payload['ref'] ?? $config['defaultBranch'] ?? 'origin/main'));
        if ($ref !== '' && !str_starts_with($ref, 'origin/')) {
            $defaultBranch = trim((string) ($config['defaultBranch'] ?? 'main'));
            if ($ref === $defaultBranch) {
                $ref = 'origin/' . $defaultBranch;
            }
        }

        try {
            $this->assertAllowedRef($ref, $config);
        } catch (InvalidArgumentException $e) {
            return new JobRunResult(false, $e->getMessage(), ['ref' => $ref], 'invalid_ref');
        }

        $root = $this->resolveAppRoot();
        if ($root === null) {
            return new JobRunResult(false, 'APP_ROOT not configured', ['ref' => $ref], 'missing_app_root');
        }

        $script = $root . '/scripts/deploy-instance-update.sh';
        if (!is_file($script)) {
            return new JobRunResult(false, 'Deploy script missing', ['ref' => $ref], 'missing_script');
        }

        $cacheRoot = $root . '/backend/storage/app/deploy-cache';
        $env = [
            'APP_ROOT' => $root,
            'GIT_REF' => $ref,
            'STACK_DIR' => getenv('STACK_DIR') ?: ($_ENV['STACK_DIR'] ?? ''),
            'BACKEND_PORT' => getenv('BACKEND_PORT') ?: ($_ENV['BACKEND_PORT'] ?? '8089'),
            'DEPLOY_CACHE_ROOT' => $cacheRoot,
            'COMPOSER_HOME' => $cacheRoot . '/composer',
        ];

        $command = $this->buildCommand($script, $env);
        $outputLines = [];
        $exitCode = 1;

        set_time_limit(0);
        exec($command . ' 2>&1', $outputLines, $exitCode);
        $output = implode("\n", $outputLines);

        if ($exitCode !== 0) {
            return new JobRunResult(
                false,
                'Deploy script failed (exit ' . $exitCode . ')',
                ['ref' => $ref, 'output' => $this->tailOutput($output)],
                'script_failed'
            );
        }

        return new JobRunResult(
            true,
            'Deploy completed',
            ['ref' => $ref, 'output' => $this->tailOutput($output)]
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public function assertAllowedRef(string $ref, array $config): void
    {
        if ($ref === '' || preg_match('/[^a-zA-Z0-9._\\/-]/', $ref) === 1) {
            throw new InvalidArgumentException('Invalid deploy ref');
        }

        if (str_starts_with($ref, 'origin/')) {
            if (!(bool) ($config['allowDeployMain'] ?? false)) {
                throw new InvalidArgumentException('Branch deploy is not allowed');
            }

            return;
        }

        if (preg_match('/^v\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $ref) === 1) {
            if (!(bool) ($config['allowDeployTags'] ?? true)) {
                throw new InvalidArgumentException('Tag deploy is not allowed');
            }

            return;
        }

        throw new InvalidArgumentException('Deploy ref must be origin/branch or semver tag');
    }

    /**
     * @param array<string, string> $env
     */
    private function buildCommand(string $script, array $env): string
    {
        $parts = [];
        foreach ($env as $key => $value) {
            if ($value === '') {
                continue;
            }
            $parts[] = $key . '=' . escapeshellarg($value);
        }
        $parts[] = escapeshellarg($script);

        return implode(' ', $parts);
    }

    private function resolveAppRoot(): ?string
    {
        return AppRoot::resolve($this->appRoot);
    }

    private function isTesting(): bool
    {
        return (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing';
    }

    private function tailOutput(string $output, int $maxLines = 40): string
    {
        $lines = explode("\n", $output);
        if (count($lines) <= $maxLines) {
            return $output;
        }

        return implode("\n", array_slice($lines, -$maxLines));
    }
}
