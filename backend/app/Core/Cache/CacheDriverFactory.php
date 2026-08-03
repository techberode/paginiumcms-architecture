<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;
use PaginiumCMS\Core\Cache\Drivers\ChainedDriver;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\Cache\Exception\UnknownCacheDriverException;

/**
 * Allow-listed cache driver resolver with safe Classic defaults (Iteration 69).
 */
final class CacheDriverFactory
{
    /** @var list<string> */
    public const ALLOWED_DRIVERS = ['auto', 'memory', 'file', 'redis'];

    /** @var list<string> */
    public const ACTIVE_DRIVERS = ['auto', 'memory', 'file'];

    public const DEFAULT_DRIVER = 'auto';

    public const DEFAULT_TTL_SECONDS = 300;

    public function __construct(
        private string $cachePath,
    ) {
    }

    public function create(?string $driver = null, bool $allowFallback = true): CacheDriverInterface
    {
        $requested = $driver ?? self::DEFAULT_DRIVER;

        if ($allowFallback) {
            $normalized = self::normalizeDriver($requested);
        } elseif (!in_array($requested, self::ACTIVE_DRIVERS, true)) {
            throw new UnknownCacheDriverException($requested);
        } else {
            $normalized = $requested;
        }

        return match ($normalized) {
            'memory' => $this->memoryDriver(),
            'file' => $this->fileDriver(),
            'auto' => $this->chainedDriver(),
            default => throw new UnknownCacheDriverException($requested),
        };
    }

    /**
     * Bootstrap-safe driver resolution — unknown values fall back to auto (memory + file).
     */
    public static function normalizeDriver(string $driver): string
    {
        $driver = strtolower(trim($driver));

        if ($driver === 'redis') {
            return self::DEFAULT_DRIVER;
        }

        if (!in_array($driver, self::ACTIVE_DRIVERS, true)) {
            return self::DEFAULT_DRIVER;
        }

        return $driver;
    }

    /**
     * @param array<string, mixed> $engineGroup
     */
    public static function driverFromEngineSettings(array $engineGroup): string
    {
        $driver = (string) ($engineGroup['cacheDriver'] ?? self::DEFAULT_DRIVER);

        return self::normalizeDriver($driver);
    }

    /**
     * @param array<string, mixed> $engineGroup
     */
    public static function defaultTtlFromEngineSettings(array $engineGroup): int
    {
        $ttl = (int) ($engineGroup['cacheDefaultTtlSeconds'] ?? self::DEFAULT_TTL_SECONDS);

        if ($ttl < 60) {
            return 60;
        }

        if ($ttl > 86400) {
            return 86400;
        }

        return $ttl;
    }

    /**
     * @param array<string, mixed> $engineGroup
     */
    public static function httpValidatorsEnabled(array $engineGroup): bool
    {
        return ($engineGroup['httpValidatorsEnabled'] ?? true) !== false;
    }

    private function memoryDriver(): MemoryDriver
    {
        return new MemoryDriver();
    }

    private function fileDriver(): FileDriver
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        return new FileDriver($this->cachePath);
    }

    private function chainedDriver(): ChainedDriver
    {
        return new ChainedDriver($this->memoryDriver(), $this->fileDriver());
    }
}
