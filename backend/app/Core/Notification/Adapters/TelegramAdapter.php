<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;

class TelegramAdapter implements AdapterInterface
{
    public function __construct(
        private string $botToken,
        private string $chatId
    ) {
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        if (preg_match('/^\d+:[A-Za-z0-9_-]+$/', $this->botToken) !== 1) {
            return false;
        }

        $text = '*' . $subject . "*\n" . $message;
        $url = 'https://api.telegram.org/bot' . $this->botToken . '/sendMessage';
        if (!OutboundUrlGuard::fromEnv()->isAllowed($url)) {
            return false;
        }
        $payload = http_build_query([
            'chat_id' => $this->chatId,
            'text' => mb_substr($text, 0, 4000),
            'parse_mode' => 'Markdown',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);

        return @file_get_contents($url, false, $context) !== false;
    }
}
