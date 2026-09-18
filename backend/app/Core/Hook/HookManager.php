<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Hook;

use PaginiumCMS\Core\Hook\Services\SafeHookRunner;

class HookManager
{
    /** @var array<int|string, mixed> */
    private array $hooks = [];

    public function __construct(private SafeHookRunner $runner = new SafeHookRunner())
    {
    }

    public function add(string $hook, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hook][$priority][] = $callback;
    }

    /**
     * @param array<int|string, mixed> $args
     * @return array<int|string, mixed>
     */
    public function run(string $hook, array $args = []): array
    {
        $result = [];
        $callbacks = $this->getCallbacks($hook);

        foreach ($callbacks as $callback) {
            if (!is_callable($callback)) {
                continue;
            }
            $result[] = $this->runner->run($hook, $callback, $args);
        }

        return $result;
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function runFirst(string $hook, array $args = []): mixed
    {
        $callbacks = $this->getCallbacks($hook);
        if ($callbacks === []) {
            return null;
        }

        $callback = $callbacks[0];
        if (!is_callable($callback)) {
            return null;
        }

        return $this->runner->run($hook, $callback, $args);
    }

    public function has(string $hook): bool
    {
        return isset($this->hooks[$hook]) && !empty($this->hooks[$hook]);
    }

    public function remove(string $hook): void
    {
        unset($this->hooks[$hook]);
    }

    /**
     * @return array<int|string, mixed>
     */
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
