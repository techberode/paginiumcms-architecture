<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

class DiscordAdapter implements AdapterInterface
{
    public function __construct(private string $webhookUrl)
    {
    }

    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        $content = '**' . $subject . "**\n" . $message;
        if ($to !== '') {
            $content = '@' . $to . ' ' . $content;
        }

        $payload = json_encode(['content' => mb_substr($content, 0, 1900)], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        return @file_get_contents($this->webhookUrl, false, $context) !== false;
    }
}
