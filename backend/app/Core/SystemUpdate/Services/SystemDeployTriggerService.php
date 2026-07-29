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

        $ref = trim($ref);
        if ($ref === '') {
            return $this->fail('Deploy ref is required', 422);
        }

        try {
            $this->deploy->assertAllowedRef($ref, $config);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        if ($this->registry->find(self::JOB_ID) === null) {
            return $this->fail('System deploy job is not registered', 503);
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
}
