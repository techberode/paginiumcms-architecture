<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Hook;

class HookManager
{
    private array $hooks = [];

    public function add(string $hook, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hook][$priority][] = $callback;
    }

    public function run(string $hook, array $args = []): array
    {
        $result = [];
        $callbacks = $this->getCallbacks($hook);

        foreach ($callbacks as $callback) {
            $result[] = call_user_func_array($callback, $args);
        }

        return $result;
    }

    public function runFirst(string $hook, array $args = [])
    {
        $callbacks = $this->getCallbacks($hook);
        if (!empty($callbacks)) {
            return call_user_func_array($callbacks[0], $args);
        }
        return null;
    }

    public function has(string $hook): bool
    {
        return isset($this->hooks[$hook]) && !empty($this->hooks[$hook]);
    }

    public function remove(string $hook): void
    {
        unset($this->hooks[$hook]);
    }

    private function getCallbacks(string $hook): array
    {
        $callbacks = [];
        if (isset($this->hooks[$hook])) {
            krsort($this->hooks[$hook]);
            foreach ($this->hooks[$hook] as $priority) {
                $callbacks = array_merge($callbacks, $priority);
            }
        }
        return $callbacks;
    }
}
