<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

interface OutboundMailSenderInterface
{
    public function send(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody): bool;
}
