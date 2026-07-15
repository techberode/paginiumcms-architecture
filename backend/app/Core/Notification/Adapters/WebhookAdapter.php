<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

class WebhookAdapter implements AdapterInterface
{
    public function __construct(
        private string $url,
        private string $secret = ''
    ) {
    }

    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
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
