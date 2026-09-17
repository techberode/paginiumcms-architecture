<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

interface OutboundMailSenderInterface
{
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
    ): bool;
}
