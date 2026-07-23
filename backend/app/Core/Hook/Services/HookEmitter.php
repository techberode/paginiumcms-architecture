<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Hook\Services;

use PaginiumCMS\Core\Hook\HookCatalog;
use PaginiumCMS\Core\Hook\HookManager;

/**
 * Typed facade over HookManager — Core emits, extensions subscribe (Wave 5d).
 */
final class HookEmitter
{
    public function __construct(private HookManager $hooks)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function emit(string $hook, array $context = []): void
    {
        if (!HookCatalog::isRegistered($hook)) {
            return;
        }

        $this->hooks->run($hook, [$context]);
    }

    /**
     * @return array<string, array{description: string, payload: list<string>}>
     */
    public function catalog(): array
    {
        return HookCatalog::describe();
    }

    public function hasListeners(string $hook): bool
    {
        return $this->hooks->has($hook);
    }
}
