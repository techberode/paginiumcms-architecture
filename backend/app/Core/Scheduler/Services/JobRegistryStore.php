<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Flat-file CRUD for scheduled job definitions (Iteration 29).
 */
final class JobRegistryStore
{
    private const REGISTRY = 'data/jobs/registry.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->load()['jobs'] ?? $this->seedDefaults()['jobs'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $job) {
            if (($job['id'] ?? '') === $id) {
                return $job;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    public function save(array $job): array
    {
        $id = (string) ($job['id'] ?? '');
        if ($id === '') {
            throw new \InvalidArgumentException('Job id is required');
        }

        $jobs = $this->all();
        $found = false;
        foreach ($jobs as $index => $existing) {
            if (($existing['id'] ?? '') === $id) {
                if (($existing['system'] ?? false) === true) {
                    $job['system'] = true;
                    $job['handler'] = $existing['handler'] ?? $job['handler'] ?? '';
                }
                $jobs[$index] = $this->normalize($job, $existing);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $jobs[] = $this->normalize($job, null);
        }

        $this->persist(['jobs' => array_values($jobs)]);

        return $this->find($id) ?? $job;
    }

    public function delete(string $id): bool
    {
        $jobs = $this->all();
        $next = [];
        $deleted = false;
        foreach ($jobs as $job) {
            if (($job['id'] ?? '') === $id) {
                if (($job['system'] ?? false) === true) {
                    return false;
                }
                $deleted = true;
                continue;
            }
            $next[] = $job;
        }

        if (!$deleted) {
            return false;
        }

        $this->persist(['jobs' => $next]);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if (!$this->reader->exists(self::REGISTRY)) {
            return $this->seedDefaults();
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read(self::REGISTRY));

            return is_array($decoded) && isset($decoded['jobs']) ? $decoded : $this->seedDefaults();
        } catch (\Throwable) {
            return $this->seedDefaults();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function seedDefaults(): array
    {
        return [
            'jobs' => [
                [
                    'id' => 'backup-scheduled',
                    'name' => 'Scheduled backup',
                    'handler' => 'backup.scheduled',
                    'cron' => '0 2 * * *',
                    'enabled' => true,
                    'system' => true,
                    'payload' => [],
                ],
                [
                    'id' => 'monitoring-pipeline',
                    'name' => 'Monitoring report + log scan',
                    'handler' => 'monitoring.pipeline',
                    'cron' => '* * * * *',
                    'enabled' => true,
                    'system' => true,
                    'payload' => [],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function persist(array $data): void
    {
        $this->writer->write(
            self::REGISTRY,
            JsonHelper::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            true
        );
    }

    /**
     * @param array<string, mixed> $job
     * @param array<string, mixed>|null $existing
     * @return array<string, mixed>
     */
    private function normalize(array $job, ?array $existing): array
    {
        return [
            'id' => (string) ($job['id'] ?? $existing['id'] ?? ''),
            'name' => (string) ($job['name'] ?? $existing['name'] ?? 'Job'),
            'handler' => (string) ($job['handler'] ?? $existing['handler'] ?? ''),
            'cron' => (string) ($job['cron'] ?? $existing['cron'] ?? '* * * * *'),
            'enabled' => (bool) ($job['enabled'] ?? $existing['enabled'] ?? false),
            'system' => (bool) ($job['system'] ?? $existing['system'] ?? false),
            'payload' => is_array($job['payload'] ?? null) ? $job['payload'] : ($existing['payload'] ?? []),
            'last_run_at' => $existing['last_run_at'] ?? null,
        ];
    }
}
