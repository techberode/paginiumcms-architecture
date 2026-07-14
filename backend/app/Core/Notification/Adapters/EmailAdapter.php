<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

class EmailAdapter implements AdapterInterface
{
    private string $from;
    private string $fromName;

    public function __construct(string $from, string $fromName = 'PaginiumCMS')
    {
        $this->from = $from;
        $this->fromName = $fromName;
    }

    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $this->fromName . ' <' . $this->from . '>',
        ];

        if (isset($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . $options['reply_to'];
        }

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
}
