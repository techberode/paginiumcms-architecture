<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

use PaginiumCMS\Core\Notification\Services\SmtpTransport;

class EmailAdapter implements AdapterInterface
{
    public function __construct(
        private string $from,
        private string $fromName = 'PaginiumCMS',
        private ?SmtpTransport $transport = null
    ) {
    }

    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        $html = $options['html'] ?? $message;
        if (!str_contains((string) $html, '<')) {
            $html = '<p>' . htmlspecialchars((string) $html, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        if ($this->transport !== null) {
            try {
                return $this->transport->send($this->from, $this->fromName, $to, $subject, (string) $html);
            } catch (\Throwable) {
                // fall through to mail()
            }
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $this->fromName . ' <' . $this->from . '>',
        ];

        if (isset($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . $options['reply_to'];
        }

        return mail($to, $subject, (string) $html, implode("\r\n", $headers));
    }
}
