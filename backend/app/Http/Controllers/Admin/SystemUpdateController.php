<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Scheduler\Services\JobQueueStore;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Scheduler\Services\JobWorker;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\SystemUpdate\Services\GitHubReleaseClient;
use PaginiumCMS\Core\SystemUpdate\Services\GitRepositoryInspector;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;
use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateVersionMatcher;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\AppVersion;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin system update — status, GitHub compare, deploy enqueue (It.63 MVP).
 */
final class SystemUpdateController
{
    private const JOB_ID = 'system-deploy';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private GitRepositoryInspector $git,
        private GitHubReleaseClient $github,
        private SystemDeployService $deploy,
        private JobRegistryStore $registry,
        private JobRunStore $runs,
        private JobQueueStore $queue,
        private JobWorker $worker,
        private SecurityAuditStore $audit,
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

        $body = json_decode((string) $request->getBody(), true);
        if (!is_array($body)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $ref = trim((string) ($body['ref'] ?? ''));
        if ($ref === '') {
            $config = $this->settings->group('systemUpdate');
            $branch = trim((string) ($config['defaultBranch'] ?? 'main'));
            $ref = 'origin/' . ($branch !== '' ? $branch : 'main');
        }

        $config = $this->settings->group('systemUpdate');
        if (!(bool) ($config['deployEnabled'] ?? false)) {
            return $this->json->error($response, 'System deploy is disabled in settings', 403);
        }

        try {
            $this->deploy->assertAllowedRef($ref, $config);
        } catch (\InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        if ($this->registry->find(self::JOB_ID) === null) {
            return $this->json->error($response, 'System deploy job is not registered', 503);
        }

        /** @var User|null $user */
        $user = $request->getAttribute('user');
        $payload = ['ref' => $ref];

        $queueId = $this->queue->enqueue(self::JOB_ID, $payload);
        $processed = $this->worker->process(1);
        $result = $processed['results'][0] ?? null;

        $this->audit->append(
            'system.deploy',
            'warning',
            'System deploy triggered',
            $user instanceof User ? $user->getId() : null,
            $user instanceof User ? $user->getEmail() : null,
            null,
            ['ref' => $ref, 'queue_id' => $queueId, 'result' => $result]
        );

        return $this->json->success($response, [
            'queued' => true,
            'queue_id' => $queueId,
            'ref' => $ref,
            'result' => $result,
        ]);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function publicConfig(array $config): array
    {
        unset($config['githubToken']);

        return $config;
    }
}
