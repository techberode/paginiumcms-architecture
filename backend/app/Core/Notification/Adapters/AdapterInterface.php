<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

interface AdapterInterface
{
    /**
     * @param array<int|string, mixed> $options
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool;
}
