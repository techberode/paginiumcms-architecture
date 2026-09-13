<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Scheduler\Services\JobQueueStore;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Scheduler\Services\JobWorker;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;

/**
 * Enqueues and runs the system-deploy job (shared by admin UI + GitHub webhook).
 */
final class SystemDeployTriggerService
{
    private const JOB_ID = 'system-deploy';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private SystemDeployService $deploy,
        private SystemDeployReadinessService $readiness,
        private JobRegistryStore $registry,
        private JobQueueStore $queue,
        private JobWorker $worker,
        private JobRunStore $runs,
        private SecurityAuditStore $audit
    ) {
    }

    /**
     * @return array{
     *     ok: bool,
     *     http_status: int,
     *     error?: string,
     *     queued?: bool,
     *     queue_id?: string,
     *     ref?: string,
     *     result?: array<string, mixed>|null,
     *     skipped?: bool,
     *     reason?: string
     * }
     * @param array<string, mixed> $auditContext
     */
    public function trigger(
        string $ref,
        ?User $user,
        string $auditEvent,
        array $auditContext = []
    ): array {
        if (DemoMode::isEnabledFromEnv()) {
            return $this->fail('System update is disabled on demo instance', 403);
        }

        $config = $this->settings->group('systemUpdate');
        if (!(bool) ($config['deployEnabled'] ?? false)) {
            return $this->fail('System deploy is disabled in settings', 403);
        }

        $ref = $this->deploy->normalizeDeployRef(trim($ref));
        if ($ref === '') {
            return $this->fail('Deploy ref is required', 422);
        }

        try {
            $this->deploy->assertAllowedRef($ref, $config);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $jobRegistered = $this->registry->find(self::JOB_ID) !== null;
        if (!$jobRegistered) {
            return $this->fail('System deploy job is not registered', 503);
        }

        $readiness = $this->readiness->evaluate($jobRegistered);
        if ($readiness['ready'] !== true) {
            return $this->fail(
                'Deploy is not ready: ' . implode(', ', $readiness['blockers']),
                503
            );
        }

        if ($this->hasRecentSuccessfulRun($ref)) {
            return [
                'ok' => true,
                'http_status' => 200,
                'skipped' => true,
                'reason' => 'already_deployed_recently',
                'ref' => $ref,
            ];
        }

        $payload = ['ref' => $ref];
        $queueId = $this->queue->enqueue(self::JOB_ID, $payload);
        $processed = $this->worker->process(1);
        $result = $processed['results'][0] ?? null;

        if (!$this->isTesting() && is_array($result) && ($result['success'] ?? false) !== true) {
            $message = is_string($result['message'] ?? null) ? $result['message'] : 'Deploy failed';
            $output = '';
            if (is_array($result['data'] ?? null) && is_string($result['data']['output'] ?? null)) {
                $output = trim($result['data']['output']);
            }

            return $this->fail(
                $output !== '' ? $message . "\n" . $output : $message,
                502
            );
        }

        $this->audit->append(
            $auditEvent,
            'warning',
            'System deploy triggered',
            $user instanceof User ? $user->getId() : null,
            $user instanceof User ? $user->getEmail() : null,
            null,
            array_merge(['ref' => $ref, 'queue_id' => $queueId, 'result' => $result], $auditContext)
        );

        return [
            'ok' => true,
            'http_status' => 200,
            'queued' => true,
            'queue_id' => $queueId,
            'ref' => $ref,
            'result' => is_array($result) ? $result : null,
        ];
    }

    private function hasRecentSuccessfulRun(string $ref): bool
    {
        foreach ($this->runs->forJob(self::JOB_ID, 5) as $run) {
            if (($run['success'] ?? false) !== true) {
                continue;
            }
            $data = is_array($run['data'] ?? null) ? $run['data'] : [];
            if ((string) ($data['ref'] ?? '') === $ref) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{ok: false, http_status: int, error: string}
     */
    private function fail(string $message, int $httpStatus): array
    {
        return [
            'ok' => false,
            'http_status' => $httpStatus,
            'error' => $message,
        ];
    }

    private function isTesting(): bool
    {
        return (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing';
    }
}
