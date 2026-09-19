<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

use PaginiumCMS\Core\Mail\Services\MailRecipientParser;
use PaginiumCMS\Core\Notification\Services\SmtpTransport;

class EmailAdapter implements AdapterInterface
{
    public function __construct(
        private string $from,
        private string $fromName = 'PaginiumCMS',
        private ?SmtpTransport $transport = null
    ) {
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        $html = $options['html'] ?? $message;
        if (!str_contains((string) $html, '<')) {
            $html = '<p>' . htmlspecialchars((string) $html, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $from = $this->from;
        $fromName = $this->fromName;
        $override = isset($options['from']) ? strtolower(trim((string) $options['from'])) : '';
        if ($override !== '' && filter_var($override, FILTER_VALIDATE_EMAIL) !== false) {
            $from = $override;
        }
        $overrideName = trim((string) ($options['from_name'] ?? ''));
        if ($overrideName !== '') {
            $fromName = $overrideName;
        }

        if ($this->transport !== null) {
            try {
                $recipients = MailRecipientParser::parse($to);
                if ($recipients === []) {
                    return false;
                }

                return $this->transport->send($from, $fromName, $recipients, $subject, (string) $html);
            } catch (\Throwable) {
                // fall through to mail()
            }
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $fromName . ' <' . $from . '>',
        ];

        if (isset($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . $options['reply_to'];
        }

        return mail($to, $subject, (string) $html, implode("\r\n", $headers));
    }
}
