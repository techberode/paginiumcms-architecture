<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;
use PaginiumCMS\Core\Cache\Drivers\ChainedDriver;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\Cache\Drivers\RedisDriver;
use PaginiumCMS\Core\Cache\Exception\UnknownCacheDriverException;

/**
 * Allow-listed cache driver resolver with safe Classic defaults (Iteration 69).
 */
final class CacheDriverFactory
{
    /** @var list<string> */
    public const ALLOWED_DRIVERS = ['auto', 'memory', 'file', 'redis'];

    /** @var list<string> */
    public const ACTIVE_DRIVERS = ['auto', 'memory', 'file', 'redis'];

    public const DEFAULT_DRIVER = 'auto';

    public const DEFAULT_TTL_SECONDS = 300;

    public function __construct(
        private string $cachePath,
    ) {
    }

    /**
     * @param array<string, mixed> $engineSettings
     */
    public function create(
        ?string $driver = null,
        bool $allowFallback = true,
        array $engineSettings = [],
    ): CacheDriverInterface {
        $requested = self::normalizeConfiguredDriver($driver ?? self::DEFAULT_DRIVER);

        return match ($requested) {
            'memory' => $this->memoryDriver(),
            'file' => $this->fileDriver(),
            'redis' => $this->createRedisStack($engineSettings, $allowFallback),
            'auto' => $this->createAutoStack($engineSettings),
            default => throw new UnknownCacheDriverException($requested),
        };
    }

    /**
     * Validates configured driver name (does not probe Redis).
     */
    public static function normalizeConfiguredDriver(string $driver): string
    {
        $driver = strtolower(trim($driver));

        if (!in_array($driver, self::ALLOWED_DRIVERS, true)) {
            return self::DEFAULT_DRIVER;
        }

        return $driver;
    }

    /**
     * @param array<string, mixed> $engineGroup
     */
    public static function driverFromEngineSettings(array $engineGroup): string
    {
        return self::normalizeConfiguredDriver((string) ($engineGroup['cacheDriver'] ?? self::DEFAULT_DRIVER));
    }

    /**
     * Label reported in health/probes for the resolved stack.
     *
     * @param array<string, mixed> $engineSettings
     */
    public function resolvedDriverName(array $engineSettings): string
    {
        $configured = self::driverFromEngineSettings($engineSettings);
        if ($configured === 'redis') {
            return $this->tryRedisDriver($engineSettings) !== null ? 'redis' : 'auto';
        }
        if ($configured === 'auto') {
            return $this->tryRedisDriver($engineSettings) !== null ? 'auto' : 'auto';
        }

        return $configured;
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

    /**
     * @param array<string, mixed> $engineSettings
     */
    private function createAutoStack(array $engineSettings): CacheDriverInterface
    {
        $redis = $this->tryRedisDriver($engineSettings);
        if ($redis !== null) {
            return new ChainedDriver($this->memoryDriver(), $redis, 'auto');
        }

        return $this->chainedFileStack();
    }

    /**
     * @param array<string, mixed> $engineSettings
     */
    private function createRedisStack(array $engineSettings, bool $allowFallback): CacheDriverInterface
    {
        $redis = $this->tryRedisDriver($engineSettings);
        if ($redis !== null) {
            return new ChainedDriver($this->memoryDriver(), $redis, 'redis');
        }

        if (!$allowFallback) {
            throw new UnknownCacheDriverException('redis');
        }

        return $this->chainedFileStack();
    }

    private function chainedFileStack(): ChainedDriver
    {
        return new ChainedDriver($this->memoryDriver(), $this->fileDriver(), 'auto');
    }

    /**
     * @param array<string, mixed> $engineSettings
     */
    private function tryRedisDriver(array $engineSettings): ?RedisDriver
    {
        $config = RedisDriverConfig::fromEngineAndEnv($engineSettings);
        if ($config === null) {
            return null;
        }

        return RedisDriver::connect($config);
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
}
