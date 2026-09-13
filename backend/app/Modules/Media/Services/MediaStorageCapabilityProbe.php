<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;

/**
 * Reports media storage driver capabilities without leaking credentials (Iteration 72).
 */
final class MediaStorageCapabilityProbe
{
    public function __construct(
        private MediaStorageFactory $factory,
        private OutboundUrlGuard $outboundUrlGuard,
    ) {
    }

    /**
     * @param array<string, mixed> $mediaSettings
     * @return array<string, mixed>
     */
    public function probe(MediaStorageDriverInterface $driver, array $mediaSettings): array
    {
        $configured = (string) ($mediaSettings['storageDriver'] ?? MediaStorageFactory::DEFAULT_DRIVER);
        $active = MediaStorageFactory::driverFromMediaSettings($mediaSettings, $this->outboundUrlGuard);
        $health = $driver->health();
        $s3Config = S3MediaStorageConfig::tryFromSettings($mediaSettings, $this->outboundUrlGuard);

        $s3Status = 'unavailable';
        $s3Message = 'S3-compatible driver is not configured.';
        $s3Health = null;

        if ($s3Config !== null) {
            $s3Status = $active === 's3' ? 'available' : 'configured';
            $s3Message = $active === 's3'
                ? 'S3-compatible driver is active.'
                : 'S3 settings are present but the active driver fell back to local.';

            try {
                $s3Driver = $this->factory->create('s3', false, $mediaSettings);
                $s3Health = $s3Driver->health();
                if (!$s3Health['ok']) {
                    $s3Status = 'failing';
                    $s3Message = 'S3-compatible driver health probe failed.';
                } elseif ($active !== 's3') {
                    $s3Status = 'configured';
                }
            } catch (\Throwable) {
                $s3Status = 'failing';
                $s3Message = 'S3-compatible driver could not be initialized.';
            }
        }

        return [
            'storageDriver' => [
                'configured' => $configured,
                'active' => $active,
                'status' => $configured === $active ? 'active' : 'fallback',
            ],
            'capabilities' => [
                'localStorage' => [
                    'status' => $health['driver'] === 'local' && $health['ok'] ? 'available' : ($health['ok'] ? 'available' : 'failing'),
                    'message' => $health['driver'] === 'local' ? $health['message'] : 'Local driver available as fallback.',
                ],
                's3Storage' => [
                    'status' => $s3Status,
                    'message' => $s3Message,
                    'summary' => $s3Config?->redactedSummary(),
                ],
            ],
            'health' => $health,
            's3Health' => $s3Health,
        ];
    }
}
