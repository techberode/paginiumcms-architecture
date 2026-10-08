<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Shared payload for GET /api/admin/jobs (HTTP + cache warm-up).
 */
final class AdminJobsOverviewProvider
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private JobRegistryStore $registry,
        private JobRunStore $runs,
        private JobQueueStore $queue,
        private JobHandlerRegistry $handlers,
        private CronExpressionEvaluator $cron,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $scheduler = $this->settings->group('scheduler');

        return [
            'enabled' => (bool) ($scheduler['enabled'] ?? true),
            'handlers' => $this->handlers->catalog(),
            'jobs' => array_map(fn (array $job): array => $this->enrichJob($job), $this->registry->all()),
            'recent_runs' => $this->runs->recent(150),
            'queue' => $this->queue->snapshot(),
            'cron_hint' => $this->buildCronHint(),
        ];
    }

    private function buildCronHint(): string
    {
        $root = realpath(dirname(__DIR__, 5));
        if ($root === false) {
            $envRoot = getenv('APP_ROOT') ?: ($_ENV['APP_ROOT'] ?? '');
            $root = is_string($envRoot) && $envRoot !== '' ? $envRoot : '/var/www/paginiumcms.com';
        }

        return sprintf(
            '* * * * * cd %s && php backend/bin/console scheduler:run && php backend/bin/console worker:process',
            $root
        );
    }

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    public function enrichJob(array $job): array
    {
        $cron = (string) ($job['cron'] ?? '* * * * *');
        $jobId = (string) ($job['id'] ?? '');
        $lastRun = isset($job['last_run_at']) ? (string) $job['last_run_at'] : null;
        $lastEntry = $jobId !== '' ? ($this->runs->forJob($jobId, 1)[0] ?? null) : null;

        if ($lastEntry !== null) {
            $fromRun = (string) ($lastEntry['finished_at'] ?? '');
            if ($fromRun !== '' && ($lastRun === null || $lastRun === '')) {
                $lastRun = $fromRun;
            }
        }

        $lastOutcome = is_array($lastEntry) ? $this->resolveRunOutcome($lastEntry) : null;

        return array_merge($job, [
            'last_run_at' => $lastRun !== '' ? $lastRun : null,
            'last_outcome' => $lastOutcome,
            'last_message' => is_array($lastEntry) ? ($lastEntry['message'] ?? null) : null,
            'next_run' => $this->cron->describeNextRun($cron),
            'due_now' => (bool) ($job['enabled'] ?? false) && $this->cron->isDueSinceLastRun($cron, $lastRun),
        ]);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function resolveRunOutcome(array $entry): string
    {
        $stored = (string) ($entry['outcome'] ?? '');
        if (in_array($stored, ['completed', 'skipped', 'failed'], true)) {
            return $stored;
        }

        if (($entry['success'] ?? false) === true) {
            return 'completed';
        }

        $reason = (string) ($entry['reason'] ?? '');
        if (in_array($reason, ['not_due', 'no_schedule', 'disabled', 'nothing_due', 'some_items_skipped'], true)) {
            return 'skipped';
        }

        return 'failed';
    }
}
