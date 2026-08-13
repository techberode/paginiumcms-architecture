<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\SystemUpdate\Services\GitHubReleaseClient;
use PaginiumCMS\Core\SystemUpdate\Services\GitRepositoryInspector;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployTriggerService;
use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateVersionMatcher;
use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateWebhookService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\AppVersion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin system update — status, GitHub compare, deploy enqueue (It.63).
 */
final class SystemUpdateController
{
    private const JOB_ID = 'system-deploy';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private GitRepositoryInspector $git,
        private GitHubReleaseClient $github,
        private SystemDeployTriggerService $deployTrigger,
        private JobRegistryStore $registry,
        private JobRunStore $runs,
        private SystemUpdateWebhookService $webhook,
        private SystemUpdateVersionMatcher $versionMatcher,
        private JsonResponder $json
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $gitStatus = $this->git->status();
        $config = $this->publicConfig($this->settings->group('systemUpdate'));

        return $this->json->success($response, [
            'app_version' => AppVersion::current(),
            'demo_mode' => DemoMode::isEnabledFromEnv(),
            'git' => $gitStatus,
            'config' => $config,
            'job_registered' => $this->registry->find(self::JOB_ID) !== null,
            'webhook' => $this->webhook->publicWebhookConfig(),
            'recent_runs' => $this->runs->forJob(self::JOB_ID, 10),
        ]);
    }

    public function check(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (DemoMode::isEnabledFromEnv()) {
            return $this->json->error($response, 'System update is disabled on demo instance', 403);
        }

        $gitStatus = $this->git->status();
        $config = $this->settings->group('systemUpdate');
        $remote = $this->github->check(
            $config,
            $gitStatus['commit'] ?? null,
            $gitStatus['commit_full'] ?? null
        );

        $behindBy = is_array($remote['compare'] ?? null)
            ? (int) ($remote['compare']['behind_by'] ?? 0)
            : null;

        $update = $this->versionMatcher->evaluate(
            AppVersion::current(),
            is_string($gitStatus['describe'] ?? null) ? $gitStatus['describe'] : null,
            is_string($remote['latest_release_tag'] ?? null) ? $remote['latest_release_tag'] : null,
            is_string($gitStatus['commit_full'] ?? null) ? $gitStatus['commit_full'] : ($gitStatus['commit'] ?? null),
            is_string($remote['remote_commit'] ?? null) ? $remote['remote_commit'] : null,
            $behindBy
        );

        return $this->json->success($response, [
            'git' => $gitStatus,
            'remote' => $remote,
            'update' => $update,
            'release_notes' => is_string($remote['latest_release_body'] ?? null) && $update['status'] === 'update_available'
                ? $remote['latest_release_body']
                : null,
            'release_url' => is_string($remote['latest_release_url'] ?? null) ? $remote['latest_release_url'] : null,
        ]);
    }

    public function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (DemoMode::isEnabledFromEnv()) {
            return $this->json->error($response, 'System update is disabled on demo instance', 403);
        }

        $body = RequestJsonBody::decode($request);
        if ($body === null) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $ref = trim((string) ($body['ref'] ?? ''));
        if ($ref === '') {
            $config = $this->settings->group('systemUpdate');
            if (!(bool) ($config['allowDeployMain'] ?? false)) {
                return $this->json->error(
                    $response,
                    'Deploy ref is required. Use a release tag (e.g. v2.1.0-beta.24). Branch deploy is disabled.',
                    422
                );
            }
            $branch = trim((string) ($config['defaultBranch'] ?? 'main'));
            $ref = 'origin/' . ($branch !== '' ? $branch : 'main');
        }

        /** @var User|null $user */
        $user = $request->getAttribute('user');
        $result = $this->deployTrigger->trigger($ref, $user, 'system.deploy');

        return $this->respondTrigger($response, $result);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function publicConfig(array $config): array
    {
        unset($config['githubToken'], $config['githubWebhookSecret']);

        return $config;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function respondTrigger(ResponseInterface $response, array $result): ResponseInterface
    {
        $status = (int) ($result['http_status'] ?? 500);
        if (($result['ok'] ?? false) !== true) {
            return $this->json->error(
                $response,
                is_string($result['error'] ?? null) ? $result['error'] : 'Deploy request failed',
                $status
            );
        }

        unset($result['http_status'], $result['ok']);

        return $this->json->success($response, $result);
    }
}
