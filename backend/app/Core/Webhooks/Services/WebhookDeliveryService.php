<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Webhooks\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Support\JsonHelper;

/**
 * Performs signed HTTP POST delivery for outbound webhooks (It.80d).
 */
final class WebhookDeliveryService
{
    public function __construct(
        private WebhookRegistryStore $registry,
        private WebhookDeliveryStore $deliveries,
    ) {
    }

    /**
     * @return array{success: bool, httpStatus: int, error: string}
     */
    public function deliver(string $deliveryId): array
    {
        $delivery = $this->deliveries->find($deliveryId);
        if ($delivery === null) {
            return ['success' => false, 'httpStatus' => 0, 'error' => 'Delivery not found'];
        }

        if ($delivery['status'] === 'success') {
            return ['success' => true, 'httpStatus' => $delivery['httpStatus'] ?? 200, 'error' => ''];
        }

        $webhook = $this->registry->find($delivery['webhookId']);
        if ($webhook === null || !$webhook['enabled']) {
            $this->deliveries->markFailure($deliveryId, 0, 'Webhook disabled or missing');

            return ['success' => false, 'httpStatus' => 0, 'error' => 'Webhook disabled or missing'];
        }

        $url = $webhook['url'];
        try {
            OutboundUrlGuard::fromEnv()->assertAllowed($url);
        } catch (\Throwable $exception) {
            $this->deliveries->markFailure($deliveryId, 0, $exception->getMessage());

            return ['success' => false, 'httpStatus' => 0, 'error' => $exception->getMessage()];
        }

        $secret = $this->registry->decryptSecret($webhook);
        if ($secret === '') {
            $this->deliveries->markFailure($deliveryId, 0, 'Webhook secret unavailable');

            return ['success' => false, 'httpStatus' => 0, 'error' => 'Webhook secret unavailable'];
        }

        $body = JsonHelper::encode([
            'event' => $delivery['event'],
            'timestamp' => gmdate('c'),
            'data' => is_array($delivery['payload']['data'] ?? null) ? $delivery['payload']['data'] : $delivery['payload'],
        ], JSON_UNESCAPED_UNICODE);

        $signature = hash_hmac('sha256', $body, $secret);
        $headers = [
            'Content-Type: application/json',
            'User-Agent: PaginiumCMS-Webhook/1.0',
            'X-Paginium-Event: ' . $delivery['event'],
            'X-Paginium-Signature: sha256=' . $signature,
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        /** @var list<string> $responseHeaders */
        $responseHeaders = array_key_exists('http_response_header', $GLOBALS) && is_array($GLOBALS['http_response_header'])
            ? $GLOBALS['http_response_header']
            : [];
        $httpStatus = $this->resolveHttpStatus($responseHeaders);

        if ($httpStatus >= 200 && $httpStatus < 300) {
            $this->deliveries->markSuccess($deliveryId, $httpStatus);

            return ['success' => true, 'httpStatus' => $httpStatus, 'error' => ''];
        }

        $error = is_string($responseBody) && trim($responseBody) !== ''
            ? 'HTTP ' . $httpStatus . ': ' . trim(substr($responseBody, 0, 200))
            : 'HTTP ' . $httpStatus;

        $this->deliveries->markFailure($deliveryId, $httpStatus, $error);

        return ['success' => false, 'httpStatus' => $httpStatus, 'error' => $error];
    }

    /**
     * @param list<string> $headers
     */
    private function resolveHttpStatus(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
