<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Scheduler\Contracts\JobHandlerInterface;
use PaginiumCMS\Core\Scheduler\Models\JobRunResult;
use PaginiumCMS\Core\Webhooks\Services\WebhookDeliveryService;
use PaginiumCMS\Core\Webhooks\Services\WebhookDeliveryStore;

/**
 * Processes queued outbound webhook deliveries with retry support (It.80d).
 */
final class WebhookDeliveryHandler implements JobHandlerInterface
{
    public function __construct(
        private WebhookDeliveryService $deliveryService,
        private WebhookDeliveryStore $deliveryStore,
    ) {
    }

    public function key(): string
    {
        return 'webhook.deliver';
    }

    public function label(): string
    {
        return 'Outbound webhook delivery';
    }

    public function handle(array $payload = []): JobRunResult
    {
        $deliveryId = is_string($payload['delivery_id'] ?? null) ? trim($payload['delivery_id']) : '';
        $processed = 0;
        $successes = 0;
        $failures = 0;
        $lastError = '';

        if ($deliveryId !== '') {
            $result = $this->deliveryService->deliver($deliveryId);
            ++$processed;
            if ($result['success']) {
                ++$successes;
            } else {
                ++$failures;
                $lastError = $result['error'];
            }
        }

        foreach ($this->deliveryStore->duePending(10) as $delivery) {
            $id = $delivery['id'];
            if ($id === $deliveryId) {
                continue;
            }

            $result = $this->deliveryService->deliver($id);
            ++$processed;
            if ($result['success']) {
                ++$successes;
            } else {
                ++$failures;
                $lastError = $result['error'];
            }
        }

        if ($processed === 0) {
            return new JobRunResult(true, 'No webhook deliveries due', ['processed' => 0]);
        }

        return new JobRunResult(
            $failures === 0,
            sprintf('Processed %d webhook delivery(ies): %d ok, %d failed', $processed, $successes, $failures),
            [
                'processed' => $processed,
                'successes' => $successes,
                'failures' => $failures,
            ],
            $failures > 0 ? $lastError : null
        );
    }
}
