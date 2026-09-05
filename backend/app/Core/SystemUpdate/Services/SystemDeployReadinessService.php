<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Support\AppRoot;

/**
 * Evaluates whether admin/GitHub deploy can run and returns machine-readable blockers (It.25+).
 */
final class SystemDeployReadinessService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private SystemDeployService $deploy,
    ) {
    }

    /**
     * @return array{
     *     ready: bool,
     *     blockers: list<string>,
     *     stack_dir: string,
     *     stack_dir_configured: bool,
     *     stack_script_executable: bool,
     *     backend_port: string,
     *     deploy_script_exists: bool,
     *     app_root_configured: bool,
     *     deploy_enabled: bool,
     *     allow_deploy_tags: bool
     * }
     */
    public function evaluate(bool $jobRegistered): array
    {
        $config = $this->settings->group('systemUpdate');
        $blockers = [];

        if (DemoMode::isEnabledFromEnv()) {
            $blockers[] = 'demo_mode';
        }

        $deployEnabled = (bool) ($config['deployEnabled'] ?? false);
        if (!$deployEnabled) {
            $blockers[] = 'deploy_disabled';
        }

        if (!$jobRegistered) {
            $blockers[] = 'job_not_registered';
        }

        $stackDir = $this->deploy->resolvedStackDir($config);
        $stackDirConfigured = $stackDir !== '';
        if (!$stackDirConfigured) {
            $blockers[] = 'stack_dir_missing';
        }

        $stackScriptExecutable = $stackDirConfigured
            && is_file($stackDir . '/stack.sh')
            && is_executable($stackDir . '/stack.sh');
        if ($stackDirConfigured && !$stackScriptExecutable) {
            $blockers[] = 'stack_script_missing';
        }

        $appRoot = AppRoot::resolve();
        $appRootConfigured = $appRoot !== null && is_dir($appRoot);
        if (!$appRootConfigured) {
            $blockers[] = 'app_root_missing';
        }

        $deployScript = $appRootConfigured ? $appRoot . '/scripts/deploy-instance-update.sh' : null;
        $deployScriptExists = $deployScript !== null && is_file($deployScript);
        if ($appRootConfigured && !$deployScriptExists) {
            $blockers[] = 'deploy_script_missing';
        }

        $allowDeployTags = (bool) ($config['allowDeployTags'] ?? true);
        if (!$allowDeployTags) {
            $blockers[] = 'tag_deploy_disabled';
        }

        return [
            'ready' => $blockers === [],
            'blockers' => $blockers,
            'stack_dir' => $stackDir,
            'stack_dir_configured' => $stackDirConfigured,
            'stack_script_executable' => $stackScriptExecutable,
            'backend_port' => $this->deploy->resolvedBackendPort($config),
            'deploy_script_exists' => $deployScriptExists,
            'app_root_configured' => $appRootConfigured,
            'deploy_enabled' => $deployEnabled,
            'allow_deploy_tags' => $allowDeployTags,
        ];
    }
}
