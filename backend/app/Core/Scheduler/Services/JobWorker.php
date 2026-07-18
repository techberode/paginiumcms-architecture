<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

/**
 * Processes queued manual job runs (Iteration 29).
 */
final class JobWorker
{
    public function __construct(
        private JobQueueStore $queue,
        private ScheduledJobRunner $runner
    ) {
    }

    /**
     * @return array{processed: int, results: list<array<string, mixed>>}
     */
    public function process(int $limit = 10): array
    {
        $results = [];
        $processed = 0;

        foreach ($this->queue->pending($limit) as $item) {
            $queueId = (string) ($item['id'] ?? '');
            $jobId = (string) ($item['job_id'] ?? '');
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];

            $result = $this->runner->runJobById($jobId, $payload);
            $this->queue->markDone($queueId, (bool) ($result['success'] ?? false));
            $results[] = $result;
            ++$processed;
        }

        return ['processed' => $processed, 'results' => $results];
    }
}
