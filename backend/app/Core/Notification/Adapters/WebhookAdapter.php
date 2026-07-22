<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;

class WebhookAdapter implements AdapterInterface
{
    public function __construct(
        private string $url,
        private string $secret = '',
        private string $authHeader = 'X-Webhook-Secret'
    ) {
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        // SSRF guard (C14): webhook URL je admin-konfigurovateľná.
        if (!OutboundUrlGuard::fromEnv()->isAllowed($this->url)) {
            return false;
        }

        $payload = json_encode([
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'event' => $options['event'] ?? 'notification',
            'severity' => $options['severity'] ?? 'info',
            'timestamp' => date('c'),
            'meta' => $options['meta'] ?? [],
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            return false;
        }

        $headers = ['Content-Type: application/json'];
        if ($this->secret !== '') {
            $headerName = trim($this->authHeader) !== '' ? trim($this->authHeader) : 'X-Webhook-Secret';
            $headers[] = $headerName . ': ' . $this->secret;
            $headers[] = 'X-Paginium-Signature: ' . hash_hmac('sha256', $payload, $this->secret);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        return @file_get_contents($this->url, false, $context) !== false;
    }
}
