<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Scheduler\Services\CronExpressionEvaluator;
use PaginiumCMS\Core\Scheduler\Services\JobHandlerRegistry;
use PaginiumCMS\Core\Scheduler\Services\JobQueueStore;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Scheduler\Services\JobWorker;
use PaginiumCMS\Core\Scheduler\Services\ScheduledJobRunner;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin CRUD + run controls for flat-file job registry (Iteration 29).
 */
final class JobsController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private JobRegistryStore $registry,
        private JobRunStore $runs,
        private JobQueueStore $queue,
        private JobHandlerRegistry $handlers,
        private ScheduledJobRunner $runner,
        private JobWorker $worker,
        private CronExpressionEvaluator $cron,
        private JsonResponder $json
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $scheduler = $this->settings->group('scheduler');

        return $this->json->success($response, [
            'enabled' => (bool) ($scheduler['enabled'] ?? true),
            'handlers' => $this->handlers->catalog(),
            'jobs' => array_map(fn (array $job): array => $this->enrichJob($job), $this->registry->all()),
            'recent_runs' => $this->runs->recent(20),
            'queue' => $this->queue->snapshot(),
            'cron_hint' => '* * * * * cd /path/to/paginiumcms && php backend/bin/console scheduler:run && php backend/bin/console worker:process',
        ]);
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
            'job' => $this->enrichJob($job),
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

        return $this->json->success($response, $this->enrichJob($job), 201);
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
        $error = $this->validateJobPayload($payload, false, $existing);
        if ($error !== null) {
            return $this->json->error($response, $error, 422);
        }

        $job = $this->registry->save($payload);

        return $this->json->success($response, $this->enrichJob($job));
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

        return $this->json->success($response, ['deleted' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        if ($this->registry->find($id) === null) {
            return $this->json->error($response, 'Job not found', 404);
        }

        $payload = $this->parseBody($request) ?? [];
        $async = (bool) ($payload['async'] ?? false);
        $forceReport = (bool) ($payload['force_report'] ?? false);

        if ($async) {
            $queueId = $this->queue->enqueue($id, $forceReport ? ['force_report' => true] : []);
            $processed = $this->worker->process(1);

            return $this->json->success($response, [
                'queued' => true,
                'queue_id' => $queueId,
                'result' => $processed['results'][0] ?? null,
            ]);
        }

        $runPayload = $forceReport ? ['force_report' => true] : [];
        $result = $this->runner->runJobById($id, $runPayload);

        return $this->json->success($response, ['result' => $result]);
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
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    private function enrichJob(array $job): array
    {
        $cron = (string) ($job['cron'] ?? '* * * * *');
        $lastRun = isset($job['last_run_at']) ? (string) $job['last_run_at'] : null;

        return array_merge($job, [
            'next_run' => $this->cron->describeNextRun($cron),
            'due_now' => (bool) ($job['enabled'] ?? false) && $this->cron->isDueSinceLastRun($cron, $lastRun),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseBody(ServerRequestInterface $request): ?array
    {
        $decoded = json_decode((string) $request->getBody(), true);

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
}
