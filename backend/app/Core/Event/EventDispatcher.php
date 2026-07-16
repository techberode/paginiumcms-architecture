<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Event;

class EventDispatcher
{
    /** @var array<int|string, mixed> */
    private array $listeners = [];

    public function addListener(string $event, callable $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $eventName = get_class($event);
        $listeners = $this->getListeners($eventName);

        foreach ($listeners as $listener) {
            $listener($event);
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getListeners(string $event): array
    {
        $listeners = [];
        if (isset($this->listeners[$event])) {
            krsort($this->listeners[$event]);
            foreach ($this->listeners[$event] as $priority) {
                $listeners = array_merge($listeners, $priority);
            }
        }
        return $listeners;
    }
}
