<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Webhooks\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Flat-file delivery log and retry queue for outbound webhooks (It.80d).
 *
 * @phpstan-type DeliveryRecord array{
 *     id: string,
 *     webhookId: string,
 *     event: string,
 *     payload: array<string, mixed>,
 *     attempt: int,
 *     maxAttempts: int,
 *     nextRetryAt: string|null,
 *     status: string,
 *     httpStatus: int|null,
 *     lastError: string,
 *     createdAt: string,
 *     updatedAt: string|null,
 *     deliveredAt: string|null
 * }
 */
final class WebhookDeliveryStore
{
    private const MAX_ATTEMPTS = 5;

    /** @var list<int> */
    private const BACKOFF_SECONDS = [60, 300, 900, 3600, 14400];

    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/webhooks/deliveries.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @param array<string, mixed> $payload
     * @return DeliveryRecord
     */
    public function create(string $webhookId, string $event, array $payload): array
    {
        $now = gmdate('c');
        $record = [
            'id' => 'del_' . bin2hex(random_bytes(8)),
            'webhookId' => $webhookId,
            'event' => $event,
            'payload' => $this->sanitizePayload($payload),
            'attempt' => 0,
            'maxAttempts' => self::MAX_ATTEMPTS,
            'nextRetryAt' => null,
            'status' => 'pending',
            'httpStatus' => null,
            'lastError' => '',
            'createdAt' => $now,
            'updatedAt' => null,
            'deliveredAt' => null,
        ];

        $this->withLockedStore(function (array $store) use ($record): array {
            $deliveries = $this->deliveriesFromStore($store);
            $deliveries[$record['id']] = $record;
            $store['schemaVersion'] = 1;
            $store['deliveries'] = $this->trimDeliveries(array_values($deliveries));

            return $store;
        });

        return $record;
    }

    /**
     * @return DeliveryRecord|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->loadDeliveries() as $delivery) {
            if ($delivery['id'] === $id) {
                return $delivery;
            }
        }

        return null;
    }

    /**
     * @return list<DeliveryRecord>
     */
    public function duePending(int $limit = 20): array
    {
        $now = time();
        $due = [];

        foreach ($this->loadDeliveries() as $delivery) {
            if ($delivery['status'] !== 'pending') {
                continue;
            }

            $nextRetryAt = $delivery['nextRetryAt'];
            if ($nextRetryAt !== null && strtotime($nextRetryAt) > $now) {
                continue;
            }

            $due[] = $delivery;
            if (count($due) >= $limit) {
                break;
            }
        }

        return $due;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentForWebhook(string $webhookId, int $limit = 20): array
    {
        $rows = [];

        foreach ($this->loadDeliveries() as $delivery) {
            if ($delivery['webhookId'] !== $webhookId) {
                continue;
            }
            $rows[] = $this->toPublicRow($delivery);
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? '')));

        return array_slice($rows, 0, $limit);
    }

    public function markSuccess(string $id, int $httpStatus): void
    {
        $this->updateDelivery($id, static function (array $delivery) use ($httpStatus): array {
            $delivery['status'] = 'success';
            $delivery['httpStatus'] = $httpStatus;
            $delivery['lastError'] = '';
            $delivery['deliveredAt'] = gmdate('c');
            $delivery['updatedAt'] = gmdate('c');
            $delivery['nextRetryAt'] = null;

            return $delivery;
        });
    }

    public function markFailure(string $id, int $httpStatus, string $error): void
    {
        $this->updateDelivery($id, function (array $delivery) use ($httpStatus, $error): array {
            $delivery['attempt'] = $delivery['attempt'] + 1;
            $delivery['httpStatus'] = $httpStatus > 0 ? $httpStatus : null;
            $delivery['lastError'] = LogSanitizer::value($error, 500);
            $delivery['updatedAt'] = gmdate('c');

            $attempt = $delivery['attempt'];
            $maxAttempts = $delivery['maxAttempts'];

            if ($attempt >= $maxAttempts) {
                $delivery['status'] = 'dead';
                $delivery['nextRetryAt'] = null;

                return $delivery;
            }

            $delay = self::BACKOFF_SECONDS[min($attempt - 1, count(self::BACKOFF_SECONDS) - 1)];
            $delivery['status'] = 'pending';
            $delivery['nextRetryAt'] = gmdate('c', time() + $delay);

            return $delivery;
        });
    }

    /**
     * @param callable(DeliveryRecord): DeliveryRecord $mutator
     */
    private function updateDelivery(string $id, callable $mutator): void
    {
        $this->withLockedStore(function (array $store) use ($id, $mutator): array {
            $deliveries = $this->deliveriesFromStore($store);
            if (!isset($deliveries[$id])) {
                throw new RuntimeException('Delivery not found');
            }

            $deliveries[$id] = $mutator($deliveries[$id]);
            $store['deliveries'] = $this->trimDeliveries(array_values($deliveries));

            return $store;
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $json = JsonHelper::encode($payload, JSON_UNESCAPED_UNICODE);
        $sanitized = LogSanitizer::value($json, 4000);
        $decoded = json_decode($sanitized, true);

        return is_array($decoded) ? $decoded : ['event' => (string) ($payload['event'] ?? 'unknown')];
    }

    /**
     * @param list<DeliveryRecord> $deliveries
     * @return list<DeliveryRecord>
     */
    private function trimDeliveries(array $deliveries): array
    {
        usort($deliveries, static fn (array $a, array $b): int => strcmp($b['createdAt'], $a['createdAt']));

        return array_slice($deliveries, 0, 500);
    }

    /**
     * @return array<string, DeliveryRecord>
     */
    private function loadDeliveries(): array
    {
        if (!file_exists($this->absolutePath)) {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read($this->storeFile));

            return $this->deliveriesFromStore($decoded);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<int|string, mixed> $store
     * @return array<string, DeliveryRecord>
     */
    private function deliveriesFromStore(array $store): array
    {
        $rows = [];
        $items = is_array($store['deliveries'] ?? null) ? $store['deliveries'] : [];

        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['id'], $item['webhookId'], $item['event'], $item['createdAt'])
                || !is_string($item['id'])
                || !is_string($item['webhookId'])
                || !is_string($item['event'])
                || !is_string($item['createdAt'])
            ) {
                continue;
            }

            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];

            $rows[$item['id']] = [
                'id' => $item['id'],
                'webhookId' => $item['webhookId'],
                'event' => $item['event'],
                'payload' => $payload,
                'attempt' => (int) ($item['attempt'] ?? 0),
                'maxAttempts' => (int) ($item['maxAttempts'] ?? self::MAX_ATTEMPTS),
                'nextRetryAt' => isset($item['nextRetryAt']) && is_string($item['nextRetryAt']) ? $item['nextRetryAt'] : null,
                'status' => is_string($item['status'] ?? null) ? $item['status'] : 'pending',
                'httpStatus' => isset($item['httpStatus']) ? (int) $item['httpStatus'] : null,
                'lastError' => is_string($item['lastError'] ?? null) ? $item['lastError'] : '',
                'createdAt' => $item['createdAt'],
                'updatedAt' => isset($item['updatedAt']) && is_string($item['updatedAt']) ? $item['updatedAt'] : null,
                'deliveredAt' => isset($item['deliveredAt']) && is_string($item['deliveredAt']) ? $item['deliveredAt'] : null,
            ];
        }

        return $rows;
    }

    /**
     * @param DeliveryRecord $delivery
     * @return array<string, mixed>
     */
    private function toPublicRow(array $delivery): array
    {
        return [
            'id' => $delivery['id'],
            'webhookId' => $delivery['webhookId'],
            'event' => $delivery['event'],
            'status' => $delivery['status'],
            'attempt' => $delivery['attempt'],
            'maxAttempts' => $delivery['maxAttempts'],
            'httpStatus' => $delivery['httpStatus'],
            'lastError' => $delivery['lastError'],
            'createdAt' => $delivery['createdAt'],
            'updatedAt' => $delivery['updatedAt'],
            'deliveredAt' => $delivery['deliveredAt'],
            'nextRetryAt' => $delivery['nextRetryAt'],
        ];
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    private function withLockedStore(callable $callback): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create webhook delivery store directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open webhook delivery store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock webhook delivery store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: [])
                : ['schemaVersion' => 1, 'deliveries' => []];

            if (!is_array($store)) {
                $store = ['schemaVersion' => 1, 'deliveries' => []];
            }

            $store = $callback($store);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, JsonHelper::encode($store, JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
