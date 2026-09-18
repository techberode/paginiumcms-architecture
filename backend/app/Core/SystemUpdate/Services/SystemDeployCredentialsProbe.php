<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\AppRoot;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Live verification of GitHub token + git fetch transport (admin UI deploy).
 */
final class SystemDeployCredentialsProbe
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private GitHubReleaseClient $github
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(): array
    {
        $config = $this->settings->group('systemUpdate');
        $owner = trim((string) ($config['githubOwner'] ?? ''));
        $repo = trim((string) ($config['githubRepo'] ?? ''));
        $sshAvailable = GitDeployTransport::isSshAvailable();
        $token = GitDeployTransport::resolveGithubDeployToken($config);
        $tokenUnreadable = $this->settings->hasOverride('systemUpdate', 'githubToken') && $token === '';

        $webhookEnabled = (bool) ($config['webhookDeployEnabled'] ?? false);
        $webhookSecret = trim((string) ($config['githubWebhookSecret'] ?? ''));

        $tokenStatus = 'not_required';
        $tokenDetail = null;
        $apiHttp = null;

        if ($tokenUnreadable) {
            $tokenStatus = 'unreadable';
            $tokenDetail = 'Stored token cannot be decrypted — re-save after fixing APP_KEY or use GITHUB_DEPLOY_TOKEN in .env';
        } elseif (!$sshAvailable) {
            if ($token === '') {
                $tokenStatus = 'missing';
                $tokenDetail = 'SSH is unavailable in PHP — set GitHub token (repo read) or GITHUB_DEPLOY_TOKEN';
            } else {
                $api = $this->github->probeRepositoryAccess($owner, $repo, $token);
                $apiHttp = $api['http_status'];
                if ($api['ok'] === true) {
                    $tokenStatus = 'ok';
                    $tokenDetail = 'GitHub API accepts the token for this repository';
                } else {
                    $tokenStatus = 'invalid';
                    $tokenDetail = is_string($api['error'] ?? null) ? $api['error'] : 'GitHub API probe failed';
                }
            }
        } elseif ($token !== '') {
            $api = $this->github->probeRepositoryAccess($owner, $repo, $token);
            $apiHttp = $api['http_status'];
            if ($api['ok'] === true) {
                $tokenStatus = 'ok';
                $tokenDetail = 'GitHub API accepts the token (optional while SSH is available)';
            } else {
                $tokenStatus = 'invalid';
                $tokenDetail = is_string($api['error'] ?? null) ? $api['error'] : 'GitHub API probe failed';
            }
        } else {
            $tokenStatus = 'not_required';
            $tokenDetail = 'Git SSH is available — deploy may use SSH remote without a token';
        }

        $appRoot = AppRoot::resolve();
        $gitFetch = $this->probeGitFetch($appRoot, $token, $sshAvailable);

        $webhookSecretStatus = 'not_required';
        $webhookSecretDetail = null;
        if ($webhookEnabled) {
            if ($webhookSecret === '') {
                $webhookSecretStatus = 'missing';
                $webhookSecretDetail = 'Webhook auto-deploy is on but secret is empty';
            } else {
                $webhookSecretStatus = 'ok';
                $webhookSecretDetail = 'Webhook secret is stored — signature is verified when GitHub delivers events';
            }
        } else {
            $webhookSecretDetail = 'Webhook auto-deploy is off — secret not required';
        }

        $deployCredentialsOk = $gitFetch['status'] === 'ok'
            && ($tokenStatus === 'ok' || $tokenStatus === 'not_required')
            && (!$webhookEnabled || $webhookSecretStatus === 'ok');

        return [
            'checked_at' => gmdate('c'),
            'overall_ok' => $deployCredentialsOk,
            'github' => [
                'owner' => $owner,
                'repo' => $repo,
                'token' => [
                    'status' => $tokenStatus,
                    'detail' => $tokenDetail,
                    'api_http_status' => $apiHttp,
                    'ssh_available' => $sshAvailable,
                ],
            ],
            'git_fetch' => $gitFetch,
            'webhook' => [
                'auto_deploy_enabled' => $webhookEnabled,
                'secret' => [
                    'status' => $webhookSecretStatus,
                    'detail' => $webhookSecretDetail,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     status: 'ok'|'failed'|'skipped',
     *     transport: 'ssh'|'https_token'|'https_no_token',
     *     detail: ?string,
     *     app_root: ?string
     * }
     */
    private function probeGitFetch(?string $appRoot, string $token, bool $sshAvailable): array
    {
        if ($appRoot === null || !is_dir($appRoot . '/.git')) {
            return [
                'status' => 'skipped',
                'transport' => $sshAvailable ? 'ssh' : ($token !== '' ? 'https_token' : 'https_no_token'),
                'detail' => 'Git repository not found where PHP runs — bind-mount the host checkout into the container (see DEPLOY.md §12.5)',
                'app_root' => $appRoot,
            ];
        }

        $transport = $sshAvailable ? 'ssh' : ($token !== '' ? 'https_token' : 'https_no_token');
        if (!$sshAvailable && $token === '') {
            return [
                'status' => 'failed',
                'transport' => 'https_no_token',
                'detail' => 'Cannot run git ls-remote — no SSH and no GitHub token',
                'app_root' => $appRoot,
            ];
        }

        $command = $this->buildGitLsRemoteCommand($appRoot, $token, $sshAvailable);
        $outputLines = [];
        $exitCode = 1;
        exec($command . ' 2>&1', $outputLines, $exitCode);
        $output = LogSanitizer::value(implode("\n", $outputLines), 2000);

        if ($exitCode === 0) {
            return [
                'status' => 'ok',
                'transport' => $transport,
                'detail' => 'git ls-remote origin HEAD succeeded (same transport as deploy script)',
                'app_root' => $appRoot,
            ];
        }

        return [
            'status' => 'failed',
            'transport' => $transport,
            'detail' => $output !== '' ? $output : 'git ls-remote failed',
            'app_root' => $appRoot,
        ];
    }

    private function buildGitLsRemoteCommand(string $appRoot, string $token, bool $sshAvailable): string
    {
        $parts = ['git', '-C', escapeshellarg($appRoot), '-c', 'safe.directory=' . escapeshellarg($appRoot)];

        if (!$sshAvailable && $token !== '') {
            $authBase = 'https://x-access-token:' . $token . '@github.com/';
            $parts[] = '-c';
            $parts[] = escapeshellarg('url.' . $authBase . '.insteadOf=git@github.com:');
            $parts[] = '-c';
            $parts[] = escapeshellarg('url.' . $authBase . '.insteadOf=ssh://git@github.com/');
            $parts[] = '-c';
            $parts[] = escapeshellarg('http.https://github.com/.extraHeader=Authorization: Bearer ' . $token);
        }

        $parts[] = 'ls-remote';
        $parts[] = 'origin';
        $parts[] = 'HEAD';

        return implode(' ', $parts);
    }
}
