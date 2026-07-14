<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification;

use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;

class NotificationService
{
    private array $adapters = [];

    public function addAdapter(string $name, AdapterInterface $adapter): void
    {
        $this->adapters[$name] = $adapter;
    }

    public function send(string $adapter, string $to, string $subject, string $message, array $options = []): bool
    {
        if (!isset($this->adapters[$adapter])) {
            throw new \RuntimeException("Adapter '{$adapter}' nebol nájdený");
        }

        return $this->adapters[$adapter]->send($to, $subject, $message, $options);
    }

    public function sendToAll(string $to, string $subject, string $message, array $options = []): array
    {
        $results = [];
        foreach ($this->adapters as $name => $adapter) {
            $results[$name] = $adapter->send($to, $subject, $message, $options);
        }
        return $results;
    }

    public function getAdapters(): array
    {
        return array_keys($this->adapters);
    }
}
