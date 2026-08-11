<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Webhooks\Services\OutboundWebhookDispatcher;
use PaginiumCMS\Core\Webhooks\Services\WebhookDeliveryStore;
use PaginiumCMS\Core\Webhooks\Services\WebhookRegistryStore;
use PaginiumCMS\Core\Webhooks\WebhookEventCatalog;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Admin lifecycle for outbound webhooks (It.80d).
 */
final class WebhookController
{
    public function __construct(
        private WebhookRegistryStore $store,
        private WebhookDeliveryStore $deliveries,
        private OutboundWebhookDispatcher $dispatcher,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'webhooks' => $this->store->listMetadata(),
            'availableEvents' => WebhookEventCatalog::ALL,
            'config' => [
                'encryptionEnabled' => $this->store->isEncryptionEnabled(),
            ],
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->store->isEncryptionEnabled()) {
            return $this->json->error($response, 'APP_KEY encryption is required for webhook secrets', 503);
        }

        $body = $this->parseBody($request);
        $label = is_string($body['label'] ?? null) ? trim($body['label']) : '';
        $url = is_string($body['url'] ?? null) ? trim($body['url']) : '';
        $events = is_array($body['events'] ?? null) ? $body['events'] : [];

        if ($label === '') {
            return $this->json->validation($response, 'Validation failed', ['label' => 'Label is required']);
        }
        if ($url === '') {
            return $this->json->validation($response, 'Validation failed', ['url' => 'URL is required']);
        }

        /** @var list<string> $eventList */
        $eventList = array_values(array_filter($events, static fn ($event): bool => is_string($event)));

        try {
            $created = $this->store->create($label, $url, $eventList, $this->creatorId($request));
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['webhook' => $exception->getMessage()]);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        $record = $created['record'];

        return $this->json->success($response, [
            'webhook' => [
                'id' => $record['id'],
                'label' => $record['label'],
                'url' => $record['url'],
                'events' => $record['events'],
                'enabled' => $record['enabled'],
                'createdAt' => $record['createdAt'],
                'createdBy' => $record['createdBy'],
            ],
            'secret' => $created['secret'],
            'copyOnce' => true,
        ], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = is_string($args['id'] ?? null) ? $args['id'] : '';
        if ($id === '') {
            return $this->json->error($response, 'Missing webhook id', 400);
        }

        try {
            $webhook = $this->store->update($id, $this->parseBody($request));
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Webhook not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['webhook' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['webhook' => $webhook]);
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = is_string($args['id'] ?? null) ? $args['id'] : '';
        if ($id === '') {
            return $this->json->error($response, 'Missing webhook id', 400);
        }

        try {
            $this->store->delete($id);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['deleted' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function rotate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (!$this->store->isEncryptionEnabled()) {
            return $this->json->error($response, 'APP_KEY encryption is required for webhook secrets', 503);
        }

        $id = is_string($args['id'] ?? null) ? $args['id'] : '';
        if ($id === '') {
            return $this->json->error($response, 'Missing webhook id', 400);
        }

        try {
            $rotated = $this->store->rotateSecret($id);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        $record = $rotated['record'];

        return $this->json->success($response, [
            'webhook' => [
                'id' => $record['id'],
                'label' => $record['label'],
                'url' => $record['url'],
                'events' => $record['events'],
                'enabled' => $record['enabled'],
                'updatedAt' => $record['updatedAt'],
            ],
            'secret' => $rotated['secret'],
            'copyOnce' => true,
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function test(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = is_string($args['id'] ?? null) ? $args['id'] : '';
        if ($id === '') {
            return $this->json->error($response, 'Missing webhook id', 400);
        }

        try {
            $deliveryId = $this->dispatcher->dispatchTest($id);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        $delivery = $this->deliveries->find($deliveryId);

        return $this->json->success($response, [
            'deliveryId' => $deliveryId,
            'delivery' => $delivery !== null ? [
                'id' => $delivery['id'],
                'status' => $delivery['status'],
                'httpStatus' => $delivery['httpStatus'],
                'lastError' => $delivery['lastError'],
            ] : null,
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function deliveries(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = is_string($args['id'] ?? null) ? $args['id'] : '';
        if ($id === '') {
            return $this->json->error($response, 'Missing webhook id', 400);
        }

        if ($this->store->find($id) === null) {
            return $this->json->error($response, 'Webhook not found', 404);
        }

        return $this->json->success($response, [
            'deliveries' => $this->deliveries->recentForWebhook($id),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseBody(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function creatorId(ServerRequestInterface $request): string
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? (string) $user->getId() : 'system';
    }
}
