<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Contracts;

/**
 * Extension runtime: discovery, registry sync, import, enable/disable (It.15).
 */
interface PluginManagerInterface
{
    /**
     * Register hooks for all enabled extensions (call once per request bootstrap).
     */
    public function bootEnabledExtensions(): void;

    /**
     * @return list<string>
     */
    public function getEnabledIds(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array;

    /**
     * @return array<string, mixed>
     */
    public function import(string $zipPath): array;

    public function enable(string $id): void;

    public function disable(string $id): void;

    public function uninstall(string $id): void;
}
