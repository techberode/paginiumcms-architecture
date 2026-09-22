<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Services;

use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;
use PaginiumCMS\Core\Cache\Drivers\RedisDriver;
use PaginiumCMS\Core\Cache\RedisDriverConfig;

/**
 * Reports cache driver capabilities without leaking credentials (Iteration 69).
 */
final class CacheCapabilityProbe
{
    /**
     * @param array<string, mixed> $engineSettings
     * @return array<string, mixed>
     */
    public function probe(CacheDriverInterface $driver, array $engineSettings): array
    {
        $configured = CacheDriverFactory::driverFromEngineSettings($engineSettings);
        $health = $driver->health();
        $active = $health['driver'];

        $redisMeta = $this->redisCapability($engineSettings);

        $status = 'active';
        if ($configured === 'redis' && $active !== 'redis') {
            $status = 'fallback';
        }

        return [
            'cacheDriver' => [
                'configured' => $configured,
                'active' => $active,
                'status' => $status,
            ],
            'capabilities' => [
                'fileCache' => [
                    'status' => $health['ok'] ? 'available' : 'failing',
                    'message' => is_string($health['message'] ?? null)
                        ? $health['message']
                        : 'Cache health probe completed.',
                ],
                'redisCache' => $redisMeta,
                'httpValidators' => [
                    'status' => CacheDriverFactory::httpValidatorsEnabled($engineSettings) ? 'available' : 'disabled',
                    'message' => 'ETag and Last-Modified on selected public GET routes.',
                ],
            ],
            'health' => $health,
        ];
    }

    /**
     * @param array<string, mixed> $engineSettings
     * @return array{status: string, message: string}
     */
    private function redisCapability(array $engineSettings): array
    {
        if (!extension_loaded('redis')) {
            return [
                'status' => 'unavailable',
                'message' => 'PHP ext-redis is not installed in this container.',
            ];
        }

        $config = RedisDriverConfig::fromEngineAndEnv($engineSettings);
        if ($config === null) {
            return [
                'status' => 'unavailable',
                'message' => 'Set engine.redisHost or REDIS_HOST (e.g. redis service name in Docker).',
            ];
        }

        $driver = RedisDriver::connect($config);
        if ($driver === null) {
            return [
                'status' => 'failing',
                'message' => 'Redis extension loaded but connection to ' . $config->host . ':' . $config->port . ' failed.',
            ];
        }

        $health = $driver->health();

        return [
            'status' => $health['ok'] ? 'available' : 'failing',
            'message' => $health['message'] ?? 'Redis probe completed.',
        ];
    }
}
