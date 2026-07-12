<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

interface AdapterInterface
{
    public function send(string $to, string $subject, string $message, array $options = []): bool;
}
