<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use PaginiumCMS\Core\Notification\Services\SmtpTransport;

final class SmtpOutboundMailSender implements OutboundMailSenderInterface
{
    public function __construct(private SmtpTransport $transport)
    {
    }

    public function send(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody): bool
    {
        return $this->transport->send($fromEmail, $fromName, $to, $subject, $htmlBody);
    }
}
