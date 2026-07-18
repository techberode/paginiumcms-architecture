<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Append-only run history for scheduled jobs (Iteration 29).
 */
final class JobRunStore
{
    private const RUNS = 'data/jobs/runs.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private JobRegistryStore $registry
    ) {
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function append(string $jobId, array $entry): void
    {
        $state = $this->load();
        $runs = is_array($state['runs'] ?? null) ? $state['runs'] : [];
        $runs[] = array_merge([
            'id' => uniqid('run_', true),
            'job_id' => $jobId,
            'started_at' => date('c'),
        ], $entry);

        $retain = max(50, min(500, (int) ($state['retain'] ?? 200)));
        if (count($runs) > $retain) {
            $runs = array_slice($runs, -$retain);
        }

        $this->writer->write(
            self::RUNS,
            JsonHelper::encode(['retain' => $retain, 'runs' => $runs], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            true
        );

        $job = $this->registry->find($jobId);
        if ($job !== null) {
            $job['last_run_at'] = (string) ($entry['finished_at'] ?? date('c'));
            $this->registry->save($job);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forJob(string $jobId, int $limit = 20): array
    {
        $matched = [];
        foreach (array_reverse($this->allRuns()) as $run) {
            if (($run['job_id'] ?? '') === $jobId) {
                $matched[] = $run;
                if (count($matched) >= $limit) {
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 30): array
    {
        return array_slice(array_reverse($this->allRuns()), 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allRuns(): array
    {
        $state = $this->load();
        $runs = $state['runs'] ?? [];

        return is_array($runs) ? $runs : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if (!$this->reader->exists(self::RUNS)) {
            return ['retain' => 200, 'runs' => []];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read(self::RUNS));

            return is_array($decoded) ? $decoded : ['retain' => 200, 'runs' => []];
        } catch (\Throwable) {
            return ['retain' => 200, 'runs' => []];
        }
    }
}
