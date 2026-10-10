<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Scheduler\Services\AdminJobsOverviewProvider;
use PaginiumCMS\Core\Scheduler\Services\JobHandlerRegistry;
use PaginiumCMS\Core\Scheduler\Services\JobQueueStore;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Scheduler\Services\JobWorker;
use PaginiumCMS\Core\Scheduler\Services\PrivilegedJobPolicy;
use PaginiumCMS\Core\Cache\AdminOverviewCacheService;
use PaginiumCMS\Core\Scheduler\Services\ScheduledJobRunner;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin CRUD + run controls for flat-file job registry (Iteration 29).
 */
final class JobsController
{
    public function __construct(
        private JobRegistryStore $registry,
        private JobRunStore $runs,
        private JobQueueStore $queue,
        private JobHandlerRegistry $handlers,
        private ScheduledJobRunner $runner,
        private JobWorker $worker,
        private AdminJobsOverviewProvider $jobsOverview,
        private AdminOverviewCacheService $adminOverviewCache,
        private JsonResponder $json
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $recentRunsLimit = $this->recentRunsLimitFromRequest($request);

        $payload = $this->adminOverviewCache->rememberJobsOverview(
            fn (): array => $this->jobsOverview->build($recentRunsLimit),
            $recentRunsLimit
        );

        return $this->json->success($response, $payload);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $job = $this->registry->find($id);
        if ($job === null) {
            return $this->json->error($response, 'Job not found', 404);
        }

        return $this->json->success($response, [
            'job' => $this->jobsOverview->enrichJob($job),
            'runs' => $this->runs->forJob($id, 30),
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->parseBody($request);
        if ($payload === null) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $error = $this->validateJobPayload($payload, true);
        if ($error !== null) {
            return $this->json->error($response, $error, 422);
        }

        $job = $this->registry->save($payload);
        $this->adminOverviewCache->invalidateJobsOverview();

        return $this->json->success($response, $this->jobsOverview->enrichJob($job), 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $existing = $this->registry->find($id);
        if ($existing === null) {
            return $this->json->error($response, 'Job not found', 404);
        }

        $payload = $this->parseBody($request);
        if ($payload === null) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $payload['id'] = $id;
        $privileged = $this->denyPrivilegedJobUnlessSuperAdmin($request, $response, $existing);
        if ($privileged !== null) {
            return $privileged;
        }

        $error = $this->validateJobPayload($payload, false, $existing);
        if ($error !== null) {
            return $this->json->error($response, $error, 422);
        }

        $job = $this->registry->save($payload);
        $this->adminOverviewCache->invalidateJobsOverview();

        return $this->json->success($response, $this->jobsOverview->enrichJob($job));
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        if (!$this->registry->delete($id)) {
            return $this->json->error($response, 'Job cannot be deleted (missing or system job)', 400);
        }

        $this->adminOverviewCache->invalidateJobsOverview();

        return $this->json->success($response, ['deleted' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $id = (string) ($args['id'] ?? '');
            $job = $this->registry->find($id);
            if ($job === null) {
                return $this->json->error($response, 'Job not found', 404);
            }

            $privileged = $this->denyPrivilegedJobUnlessSuperAdmin($request, $response, $job);
            if ($privileged !== null) {
                return $privileged;
            }

            $payload = $this->parseBody($request) ?? [];
            $async = (bool) ($payload['async'] ?? false);
            $forceReport = (bool) ($payload['force_report'] ?? false);

            if ($async) {
                $queueId = $this->queue->enqueue($id, $forceReport ? ['force_report' => true] : []);
                $processed = $this->worker->process(1);
                $this->adminOverviewCache->invalidateJobsOverview();

                return $this->json->success($response, [
                    'queued' => true,
                    'queue_id' => $queueId,
                    'result' => $processed['results'][0] ?? null,
                ]);
            }

            $runPayload = $forceReport ? ['force_report' => true] : [];
            $result = $this->runner->runJobById($id, $runPayload);
            $this->adminOverviewCache->invalidateJobsOverview();

            return $this->json->success($response, ['result' => $result]);
        } catch (\Throwable $e) {
            return $this->json->error($response, 'Job run failed: ' . $e->getMessage(), 422);
        }
    }

    public function runDue(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->runner->runDue(true);

        return $this->json->success($response, $result);
    }

    public function processQueue(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->parseBody($request) ?? [];
        $limit = max(1, min(50, (int) ($payload['limit'] ?? 10)));

        return $this->json->success($response, $this->worker->process($limit));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseBody(ServerRequestInterface $request): ?array
    {
        $decoded = RequestJsonBody::decode($request);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $existing
     */
    private function validateJobPayload(array $payload, bool $creating, ?array $existing = null): ?string
    {
        $id = (string) ($payload['id'] ?? ($existing['id'] ?? ''));
        if ($creating && $id === '') {
            return 'Job id is required';
        }

        if ($creating && !preg_match('/^[a-z0-9][a-z0-9-]{1,62}$/', $id)) {
            return 'Job id must be lowercase slug (a-z, 0-9, hyphen)';
        }

        if ($creating && $this->registry->find($id) !== null) {
            return 'Job id already exists';
        }

        $handler = (string) ($payload['handler'] ?? ($existing['handler'] ?? ''));
        if ($handler === '' || $this->handlers->get($handler) === null) {
            return 'Unknown handler';
        }

        $cron = (string) ($payload['cron'] ?? ($existing['cron'] ?? ''));
        $parts = preg_split('/\s+/', trim($cron)) ?: [];
        if (count($parts) !== 5) {
            return 'Cron expression must have 5 fields (minute hour day month weekday)';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $job
     */
    private function denyPrivilegedJobUnlessSuperAdmin(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $job
    ): ?ResponseInterface {
        if (!PrivilegedJobPolicy::requiresSuperAdmin($job)) {
            return null;
        }

        if ($this->isSuperAdmin($request)) {
            return null;
        }

        return $this->json->error($response, 'This job requires SUPER_ADMIN', 403);
    }

    private function isSuperAdmin(ServerRequestInterface $request): bool
    {
        $user = $request->getAttribute('user');

        return $user instanceof User && in_array('SUPER_ADMIN', $user->getRoles(), true);
    }

    private function recentRunsLimitFromRequest(ServerRequestInterface $request): int
    {
        $params = $request->getQueryParams();
        $raw = $params['recent_runs'] ?? AdminJobsOverviewProvider::DEFAULT_RECENT_RUNS_LIMIT;
        if (!is_scalar($raw)) {
            return AdminJobsOverviewProvider::DEFAULT_RECENT_RUNS_LIMIT;
        }

        return max(5, min(500, (int) $raw));
    }
}
