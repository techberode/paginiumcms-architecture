<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Services;

use PaginiumCMS\Support\AppRoot;

/**
 * Read-only wiring checks for Origin feature probes (It.82b).
 */
final class ProbeSupport
{
    private ?string $routesDir = null;

    public function classAvailable(string $class): bool
    {
        return class_exists($class);
    }

    public function routeFileContains(string $relativeRouteFile, string $needle): bool
    {
        $path = $this->routesDirectory() . '/' . ltrim($relativeRouteFile, '/');
        if (!is_readable($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        return is_string($contents) && str_contains($contents, $needle);
    }

    public function anyRouteFileContains(string $needle): bool
    {
        $dir = $this->routesDirectory();
        if (!is_dir($dir)) {
            return false;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if (!str_ends_with($entry, '.php')) {
                continue;
            }
            $contents = file_get_contents($dir . '/' . $entry);
            if (is_string($contents) && str_contains($contents, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function routesDirectory(): string
    {
        if ($this->routesDir !== null) {
            return $this->routesDir;
        }

        $root = AppRoot::resolve();
        $this->routesDir = $root !== null
            ? $root . '/backend/app/Http/Routes'
            : dirname(__DIR__, 4) . '/Http/Routes';

        return $this->routesDir;
    }
}
