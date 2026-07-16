<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Container;

class ServiceContainer
{
    /** @var array<int|string, mixed> */
    private array $services = [];
    /** @var array<int|string, mixed> */
    private array $singletons = [];
    /** @var array<int|string, mixed> */
    private array $aliases = [];

    public function set(string $name, mixed $service): void
    {
        $this->services[$name] = $service;
    }

    public function singleton(string $name, callable $factory): void
    {
        $this->singletons[$name] = $factory;
    }

    public function alias(string $alias, string $service): void
    {
        $this->aliases[$alias] = $service;
    }

    public function get(string $name): mixed
    {
        // Kontrola aliasu
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        // Kontrola singletonu
        if (isset($this->singletons[$name])) {
            $factory = $this->singletons[$name];
            $this->services[$name] = $factory($this);
            unset($this->singletons[$name]);
        }

        if (!isset($this->services[$name])) {
            throw new \RuntimeException("Služba '{$name}' nebola nájdená");
        }

        return $this->services[$name];
    }

    public function has(string $name): bool
    {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        return isset($this->services[$name]) || isset($this->singletons[$name]);
    }
}
