<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use PaginiumCMS\Core\Notification\Services\SmtpTransport;

final class SmtpOutboundMailSender implements OutboundMailSenderInterface
{
    public function __construct(private SmtpTransport $transport)
    {
    }

    /**
     * @param list<string> $recipients
     * @param list<array{contentId: string, mime: string, bytes: string}> $inlineImages
     */
    public function send(
        string $fromEmail,
        string $fromName,
        array $recipients,
        string $subject,
        string $htmlBody,
        array $inlineImages = [],
    ): bool {
        return $this->transport->send($fromEmail, $fromName, $recipients, $subject, $htmlBody, $inlineImages);
    }
}
