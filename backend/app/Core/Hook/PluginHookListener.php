<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Hook;

use InvalidArgumentException;

/**
 * Tagged plugin hook callback so SafeHookRunner can isolate and auto-disable per plugin.
 */
final class PluginHookListener
{
    /** @var callable */
    private $handler;

    public function __construct(
        public readonly string $pluginId,
        callable $handler,
    ) {
        if ($pluginId === '') {
            throw new InvalidArgumentException('Plugin id is required for a hook listener.');
        }
        $this->handler = $handler;
    }

    public function __invoke(mixed ...$args): mixed
    {
        return ($this->handler)(...$args);
    }
}
