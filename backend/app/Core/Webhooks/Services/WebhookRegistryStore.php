<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Webhooks\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Webhooks\WebhookEventCatalog;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file SSOT for outbound webhook registrations (It.80d).
 *
 * @phpstan-type WebhookRecord array{
 *     id: string,
 *     label: string,
 *     url: string,
 *     events: list<string>,
 *     secretEnc: string,
 *     enabled: bool,
 *     createdAt: string,
 *     updatedAt: string|null,
 *     createdBy: string
 * }
 */
final class WebhookRegistryStore
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private EncryptionService $encryption,
        private string $storeFile = 'data/webhooks.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listMetadata(): array
    {
        $rows = [];

        foreach ($this->loadWebhooks() as $webhook) {
            $rows[] = $this->toPublicRow($webhook);
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? '')));

        return $rows;
    }

    /**
     * @return WebhookRecord|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->loadWebhooks() as $webhook) {
            if ($webhook['id'] === $id) {
                return $webhook;
            }
        }

        return null;
    }

    /**
     * @param list<string> $events
     * @return array{record: WebhookRecord, secret: string}
     */
    public function create(string $label, string $url, array $events, string $createdBy): array
    {
        $this->assertEncryptionEnabled();
        $normalizedUrl = $this->normalizeUrl($url);
        $normalizedEvents = $this->normalizeEvents($events);

        $id = 'wh_' . bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(32));
        $now = gmdate('c');

        $record = [
            'id' => $id,
            'label' => trim($label) !== '' ? trim($label) : 'Webhook',
            'url' => $normalizedUrl,
            'events' => $normalizedEvents,
            'secretEnc' => $this->encryption->encrypt($secret),
            'enabled' => true,
            'createdAt' => $now,
            'updatedAt' => null,
            'createdBy' => $createdBy,
        ];

        $this->withLockedStore(function (array $store) use ($record): array {
            $webhooks = $this->webhooksFromStore($store);
            $webhooks[$record['id']] = $record;
            $store['schemaVersion'] = 1;
            $store['webhooks'] = array_values($webhooks);

            return $store;
        });

        return ['record' => $record, 'secret' => $secret];
    }

    /**
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    public function update(string $id, array $changes): array
    {
        $updated = null;

        $this->withLockedStore(function (array $store) use ($id, $changes, &$updated): array {
            $webhooks = $this->webhooksFromStore($store);
            if (!isset($webhooks[$id])) {
                throw new InvalidArgumentException('Webhook not found');
            }

            $record = $webhooks[$id];

            if (array_key_exists('label', $changes)) {
                $label = is_string($changes['label']) ? trim($changes['label']) : '';
                if ($label === '') {
                    throw new InvalidArgumentException('Label is required');
                }
                $record['label'] = $label;
            }

            if (array_key_exists('url', $changes)) {
                $record['url'] = $this->normalizeUrl(is_string($changes['url']) ? $changes['url'] : '');
            }

            if (array_key_exists('events', $changes)) {
                /** @var list<mixed> $events */
                $events = is_array($changes['events']) ? array_values($changes['events']) : [];
                $record['events'] = $this->normalizeEvents($events);
            }

            if (array_key_exists('enabled', $changes)) {
                $record['enabled'] = (bool) $changes['enabled'];
            }

            $record['updatedAt'] = gmdate('c');
            $webhooks[$id] = $record;
            $store['webhooks'] = array_values($webhooks);
            $updated = $record;

            return $store;
        });

        if ($updated === null) {
            throw new InvalidArgumentException('Webhook not found');
        }

        return $this->toPublicRow($updated);
    }

    /**
     * @return array{record: WebhookRecord, secret: string}
     */
    public function rotateSecret(string $id): array
    {
        $this->assertEncryptionEnabled();
        $secret = bin2hex(random_bytes(32));
        $updated = null;

        $this->withLockedStore(function (array $store) use ($id, $secret, &$updated): array {
            $webhooks = $this->webhooksFromStore($store);
            if (!isset($webhooks[$id])) {
                throw new InvalidArgumentException('Webhook not found');
            }

            $record = $webhooks[$id];
            $record['secretEnc'] = $this->encryption->encrypt($secret);
            $record['updatedAt'] = gmdate('c');
            $webhooks[$id] = $record;
            $store['webhooks'] = array_values($webhooks);
            $updated = $record;

            return $store;
        });

        if ($updated === null) {
            throw new InvalidArgumentException('Webhook not found');
        }

        return ['record' => $updated, 'secret' => $secret];
    }

    public function delete(string $id): void
    {
        $this->withLockedStore(function (array $store) use ($id): array {
            $webhooks = $this->webhooksFromStore($store);
            if (!isset($webhooks[$id])) {
                throw new InvalidArgumentException('Webhook not found');
            }

            unset($webhooks[$id]);
            $store['webhooks'] = array_values($webhooks);

            return $store;
        });
    }

    /**
     * @return list<WebhookRecord>
     */
    public function enabledForEvent(string $event): array
    {
        if (!WebhookEventCatalog::isSubscribable($event)) {
            return [];
        }

        $matches = [];
        foreach ($this->loadWebhooks() as $webhook) {
            if (!$webhook['enabled']) {
                continue;
            }
            if (in_array($event, $webhook['events'], true)) {
                $matches[] = $webhook;
            }
        }

        return $matches;
    }

    /**
     * @param WebhookRecord $webhook
     */
    public function decryptSecret(array $webhook): string
    {
        $encrypted = $webhook['secretEnc'];

        return $this->encryption->decrypt($encrypted);
    }

    public function isEncryptionEnabled(): bool
    {
        return $this->encryption->isEnabled();
    }

    private function assertEncryptionEnabled(): void
    {
        if (!$this->encryption->isEnabled()) {
            throw new RuntimeException('APP_KEY encryption is required for webhook secrets');
        }
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new InvalidArgumentException('Webhook URL is required');
        }

        try {
            OutboundUrlGuard::fromEnv()->assertAllowed($url);
        } catch (\RuntimeException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        return $url;
    }

    /**
     * @param list<mixed> $events
     * @return list<string>
     */
    private function normalizeEvents(array $events): array
    {
        $normalized = [];
        foreach ($events as $event) {
            if (!is_string($event) || !WebhookEventCatalog::isSubscribable($event)) {
                continue;
            }
            $normalized[] = $event;
        }

        $normalized = array_values(array_unique($normalized));
        if ($normalized === []) {
            throw new InvalidArgumentException('Select at least one event');
        }

        return $normalized;
    }

    /**
     * @return array<string, WebhookRecord>
     */
    private function loadWebhooks(): array
    {
        if (!file_exists($this->absolutePath)) {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read($this->storeFile));

            return $this->webhooksFromStore($decoded);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<int|string, mixed> $store
     * @return array<string, WebhookRecord>
     */
    private function webhooksFromStore(array $store): array
    {
        $rows = [];
        $items = is_array($store['webhooks'] ?? null) ? $store['webhooks'] : [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalized = $this->normalizeRecord($item);
            if ($normalized !== null) {
                $rows[$normalized['id']] = $normalized;
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $record
     * @return WebhookRecord|null
     */
    private function normalizeRecord(array $record): ?array
    {
        if (!isset($record['id'], $record['label'], $record['url'], $record['secretEnc'], $record['createdAt'])
            || !is_string($record['id'])
            || !is_string($record['label'])
            || !is_string($record['url'])
            || !is_string($record['secretEnc'])
            || !is_string($record['createdAt'])
        ) {
            return null;
        }

        $events = is_array($record['events'] ?? null) ? $record['events'] : [];
        $normalizedEvents = [];
        foreach ($events as $event) {
            if (is_string($event) && WebhookEventCatalog::isSubscribable($event)) {
                $normalizedEvents[] = $event;
            }
        }

        if ($normalizedEvents === []) {
            return null;
        }

        try {
            OutboundUrlGuard::fromEnv()->assertAllowed($record['url']);
        } catch (\Throwable) {
            return null;
        }

        return [
            'id' => $record['id'],
            'label' => trim($record['label']),
            'url' => $record['url'],
            'events' => array_values(array_unique($normalizedEvents)),
            'secretEnc' => $record['secretEnc'],
            'enabled' => (bool) ($record['enabled'] ?? true),
            'createdAt' => $record['createdAt'],
            'updatedAt' => isset($record['updatedAt']) && is_string($record['updatedAt']) ? $record['updatedAt'] : null,
            'createdBy' => isset($record['createdBy']) && is_string($record['createdBy']) ? $record['createdBy'] : '',
        ];
    }

    /**
     * @param WebhookRecord $record
     * @return array<string, mixed>
     */
    private function toPublicRow(array $record): array
    {
        return [
            'id' => $record['id'],
            'label' => $record['label'],
            'url' => $record['url'],
            'events' => $record['events'],
            'enabled' => $record['enabled'],
            'createdAt' => $record['createdAt'],
            'updatedAt' => $record['updatedAt'],
            'createdBy' => $record['createdBy'],
        ];
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    private function withLockedStore(callable $callback): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create webhook store directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open webhook store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock webhook store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: [])
                : ['schemaVersion' => 1, 'webhooks' => []];

            if (!is_array($store)) {
                $store = ['schemaVersion' => 1, 'webhooks' => []];
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
