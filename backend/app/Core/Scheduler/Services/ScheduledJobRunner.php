<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Executes due jobs from flat-file registry (Iteration 29).
 */
final class ScheduledJobRunner
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private JobRegistryStore $registry,
        private JobRunStore $runs,
        private JobHandlerRegistry $handlers,
        private CronExpressionEvaluator $cron
    ) {
    }

    /**
     * @return array{executed: int, results: list<array<string, mixed>>}
     */
    public function runDue(bool $ignoreMasterSwitch = false): array
    {
        $scheduler = $this->settings->group('scheduler');
        if (!(bool) ($scheduler['enabled'] ?? true) && !$ignoreMasterSwitch) {
            return ['executed' => 0, 'results' => []];
        }

        $results = [];
        $executed = 0;

        foreach ($this->registry->all() as $job) {
            if (!(bool) ($job['enabled'] ?? false)) {
                continue;
            }

            $id = (string) ($job['id'] ?? '');
            $handlerKey = (string) ($job['handler'] ?? '');
            $cronExpr = (string) ($job['cron'] ?? '* * * * *');
            $lastRun = isset($job['last_run_at']) ? (string) $job['last_run_at'] : null;

            if (!$this->cron->isDueSinceLastRun($cronExpr, $lastRun)) {
                continue;
            }

            $result = $this->executeJob($id, $handlerKey, is_array($job['payload'] ?? null) ? $job['payload'] : []);
            $results[] = $result;
            ++$executed;
        }

        return ['executed' => $executed, 'results' => $results];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function runJobById(string $id, array $payload = []): array
    {
        $job = $this->registry->find($id);
        if ($job === null) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        return $this->executeJob(
            $id,
            (string) ($job['handler'] ?? ''),
            array_merge(is_array($job['payload'] ?? null) ? $job['payload'] : [], $payload)
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function executeJob(string $id, string $handlerKey, array $payload): array
    {
        $handler = $this->handlers->get($handlerKey);
        if ($handler === null) {
            $entry = [
                'success' => false,
                'message' => 'Unknown handler: ' . $handlerKey,
                'finished_at' => date('c'),
            ];
            $this->runs->append($id, $entry);

            return array_merge(['job_id' => $id], $entry);
        }

        $started = microtime(true);
        try {
            $outcome = $handler->handle($payload);
            $entry = array_merge($outcome->toArray(), [
                'finished_at' => date('c'),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
        } catch (\Throwable $e) {
            $entry = [
                'success' => false,
                'message' => $e->getMessage(),
                'finished_at' => date('c'),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ];
        }

        try {
            $this->runs->append($id, $entry);
        } catch (\Throwable $e) {
            return array_merge(
                ['job_id' => $id, 'handler' => $handlerKey],
                $entry,
                [
                    'run_log_persisted' => false,
                    'run_log_error' => $e->getMessage(),
                ]
            );
        }

        return array_merge(['job_id' => $id, 'handler' => $handlerKey], $entry);
    }
}
