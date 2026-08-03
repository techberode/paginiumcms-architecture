<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Services;

use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\Contracts\CacheDriverInterface;

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
        $configured = (string) ($engineSettings['cacheDriver'] ?? CacheDriverFactory::DEFAULT_DRIVER);
        $active = CacheDriverFactory::driverFromEngineSettings($engineSettings);
        $health = $driver->health();

        return [
            'cacheDriver' => [
                'configured' => $configured,
                'active' => $active,
                'status' => $configured === $active || ($configured === 'redis' && $active === 'auto') ? 'active' : 'fallback',
            ],
            'capabilities' => [
                'fileCache' => [
                    'status' => $health['ok'] ? 'available' : 'failing',
                    'message' => is_string($health['message'] ?? null)
                        ? $health['message']
                        : 'Cache health probe completed.',
                ],
                'redisCache' => [
                    'status' => 'unavailable',
                    'message' => 'Redis driver is not installed in Iteration 69.',
                ],
                'httpValidators' => [
                    'status' => CacheDriverFactory::httpValidatorsEnabled($engineSettings) ? 'available' : 'disabled',
                    'message' => 'ETag and Last-Modified on selected public GET routes.',
                ],
            ],
            'health' => $health,
        ];
    }
}
