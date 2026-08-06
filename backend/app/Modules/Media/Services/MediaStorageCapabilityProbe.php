<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;

/**
 * Reports media storage driver capabilities without leaking credentials (Iteration 72).
 */
final class MediaStorageCapabilityProbe
{
    /**
     * @param array<string, mixed> $mediaSettings
     * @return array<string, mixed>
     */
    public function probe(MediaStorageDriverInterface $driver, array $mediaSettings): array
    {
        $configured = (string) ($mediaSettings['storageDriver'] ?? MediaStorageFactory::DEFAULT_DRIVER);
        $active = MediaStorageFactory::driverFromMediaSettings($mediaSettings);
        $health = $driver->health();

        return [
            'storageDriver' => [
                'configured' => $configured,
                'active' => $active,
                'status' => $configured === $active ? 'active' : 'fallback',
            ],
            'capabilities' => [
                'localStorage' => [
                    'status' => $health['ok'] ? 'available' : 'failing',
                    'message' => $health['message'],
                ],
                's3Storage' => [
                    'status' => 'unavailable',
                    'message' => 'S3-compatible driver is not installed in Iteration 72 MVP.',
                ],
            ],
            'health' => $health,
        ];
    }
}
