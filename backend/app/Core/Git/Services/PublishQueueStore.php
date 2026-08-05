<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Git\Models\PublishQueueItem;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file publish queue at data/git/publish-queue.json (Iteration 70).
 */
final class PublishQueueStore
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $queueFile = 'data/git/publish-queue.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/')
            . '/' . ltrim($this->queueFile, '/');
    }

    /**
     * @return list<PublishQueueItem>
     */
    public function pending(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (PublishQueueItem $item): bool => $item->status === 'pending_publish'
        ));
    }

    /**
     * @return list<PublishQueueItem>
     */
    public function all(): array
    {
        return $this->withLockedQueue(static fn (array $items): array => $items);
    }

    public function enqueue(string $resourcePath, string $fingerprint, string $action = 'upsert'): PublishQueueItem
    {
        $item = new PublishQueueItem(
            'gitq_' . bin2hex(random_bytes(8)),
            $resourcePath,
            $fingerprint,
            $action,
            'pending_publish',
            gmdate('c'),
        );

        $this->withLockedQueue(function (array &$items) use ($item, $resourcePath, $fingerprint): void {
            foreach ($items as $index => $existing) {
                if ($existing->resourcePath === $resourcePath && $existing->status === 'pending_publish') {
                    $items[$index] = new PublishQueueItem(
                        $existing->id,
                        $resourcePath,
                        $fingerprint,
                        $existing->action,
                        'pending_publish',
                        $existing->createdAt,
                    );

                    return;
                }
            }

            $items[] = $item;
        });

        return $item;
    }

    /**
     * @param list<string> $ids
     */
    public function markCommitted(array $ids, string $commitHash): void
    {
        $this->withLockedQueue(function (array &$items) use ($ids, $commitHash): void {
            foreach ($items as $index => $item) {
                if (!in_array($item->id, $ids, true)) {
                    continue;
                }

                $items[$index] = new PublishQueueItem(
                    $item->id,
                    $item->resourcePath,
                    $item->fingerprint,
                    $item->action,
                    'committed',
                    $item->createdAt,
                    gmdate('c'),
                    $commitHash,
                );
            }
        });
    }

    public function markFailed(string $id, string $error): void
    {
        $this->withLockedQueue(function (array &$items) use ($id, $error): void {
            foreach ($items as $index => $item) {
                if ($item->id !== $id) {
                    continue;
                }

                $items[$index] = new PublishQueueItem(
                    $item->id,
                    $item->resourcePath,
                    $item->fingerprint,
                    $item->action,
                    'publish_failed',
                    $item->createdAt,
                    $item->committedAt,
                    $item->commitHash,
                    $error,
                );
            }
        });
    }

    /**
     * @template T
     * @param callable(list<PublishQueueItem>&): T $callback
     * @return T
     */
    private function withLockedQueue(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open git publish queue.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock git publish queue.');
            }

            $items = $this->readItems($handle);
            $before = $this->serialize($items);
            $result = $callback($items);
            $after = $this->serialize($items);

            if ($after !== $before) {
                $this->writeItems($handle, $items);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return list<PublishQueueItem>
     */
    private function readItems($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($raw);
        } catch (\JsonException) {
            return [];
        }

        $items = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $items[] = PublishQueueItem::fromArray($entry);
        }

        return $items;
    }

    /**
     * @param resource $handle
     * @param list<PublishQueueItem> $items
     */
    private function writeItems($handle, array $items): void
    {
        $payload = array_map(static fn (PublishQueueItem $item): array => $item->toArray(), $items);
        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT);

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json);
        fflush($handle);
    }

    /**
     * @param list<PublishQueueItem> $items
     */
    private function serialize(array $items): string
    {
        $payload = array_map(static fn (PublishQueueItem $item): array => $item->toArray(), $items);

        return JsonHelper::encode($payload);
    }

    private function ensureStorage(): void
    {
        $this->writer->createDirectory('data/git');
    }
}
