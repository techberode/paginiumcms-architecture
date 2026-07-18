<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Optional queue for forced/manual job runs (Iteration 29).
 */
final class JobQueueStore
{
    private const QUEUE = 'data/jobs/queue.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueue(string $jobId, array $payload = []): string
    {
        $state = $this->load();
        $items = is_array($state['items'] ?? null) ? $state['items'] : [];
        $id = uniqid('q_', true);
        $items[] = [
            'id' => $id,
            'job_id' => $jobId,
            'payload' => $payload,
            'status' => 'pending',
            'created_at' => date('c'),
        ];
        $this->persist(['items' => $items]);

        return $id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pending(int $limit = 10): array
    {
        $items = [];
        foreach ($this->load()['items'] ?? [] as $item) {
            if (is_array($item) && ($item['status'] ?? '') === 'pending') {
                $items[] = $item;
                if (count($items) >= $limit) {
                    break;
                }
            }
        }

        return $items;
    }

    public function markDone(string $queueId, bool $success): void
    {
        $state = $this->load();
        $items = is_array($state['items'] ?? null) ? $state['items'] : [];
        foreach ($items as $index => $item) {
            if (($item['id'] ?? '') === $queueId) {
                $items[$index]['status'] = $success ? 'done' : 'failed';
                $items[$index]['finished_at'] = date('c');
                break;
            }
        }
        $this->persist(['items' => array_slice($items, -100)]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function snapshot(): array
    {
        $items = $this->load()['items'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $snapshot = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $snapshot[] = $item;
            }
        }

        return $snapshot;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function load(): array
    {
        if (!$this->reader->exists(self::QUEUE)) {
            return ['items' => []];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read(self::QUEUE));

            return isset($decoded['items']) && is_array($decoded['items']) ? $decoded : ['items' => []];
        } catch (\Throwable) {
            return ['items' => []];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function persist(array $data): void
    {
        $this->writer->write(
            self::QUEUE,
            JsonHelper::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            true
        );
    }
}
