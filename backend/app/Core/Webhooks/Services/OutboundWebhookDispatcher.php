<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Webhooks\Services;

use PaginiumCMS\Core\Scheduler\Services\JobQueueStore;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobWorker;
use PaginiumCMS\Core\Webhooks\WebhookEventCatalog;

/**
 * Queues outbound webhook deliveries for matching registrations (It.80d).
 */
final class OutboundWebhookDispatcher
{
    public const JOB_ID = 'webhook-deliver';

    public function __construct(
        private WebhookRegistryStore $registry,
        private WebhookDeliveryStore $deliveries,
        private JobRegistryStore $jobRegistry,
        private JobQueueStore $queue,
        private JobWorker $worker,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string> delivery ids
     */
    public function dispatch(string $event, array $data): array
    {
        if (!WebhookEventCatalog::isSubscribable($event)) {
            return [];
        }

        $webhooks = $this->registry->enabledForEvent($event);
        if ($webhooks === []) {
            return [];
        }

        $payload = [
            'event' => $event,
            'data' => $data,
        ];

        $deliveryIds = [];
        foreach ($webhooks as $webhook) {
            $delivery = $this->deliveries->create((string) $webhook['id'], $event, $payload);
            $deliveryIds[] = (string) $delivery['id'];
        }

        if ($this->jobRegistry->find(self::JOB_ID) !== null) {
            foreach ($deliveryIds as $deliveryId) {
                $this->queue->enqueue(self::JOB_ID, ['delivery_id' => $deliveryId]);
            }
            $this->worker->process(min(count($deliveryIds), 5));
        }

        return $deliveryIds;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function dispatchTest(string $webhookId, array $data = []): string
    {
        $webhook = $this->registry->find($webhookId);
        if ($webhook === null) {
            throw new \InvalidArgumentException('Webhook not found');
        }

        $payload = [
            'event' => WebhookEventCatalog::TEST_PING,
            'data' => array_merge([
                'message' => 'PaginiumCMS webhook test ping',
            ], $data),
        ];

        $delivery = $this->deliveries->create($webhookId, WebhookEventCatalog::TEST_PING, $payload);

        if ($this->jobRegistry->find(self::JOB_ID) !== null) {
            $this->queue->enqueue(self::JOB_ID, ['delivery_id' => $delivery['id']]);
            $this->worker->process(1);
        }

        return (string) $delivery['id'];
    }
}
