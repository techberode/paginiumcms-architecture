<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Storage\Services;

use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Core\Storage\StorageFactory;

/**
 * Reports Hybrid Engine capabilities without leaking sensitive paths (Iteration 68).
 */
final class EngineCapabilityProbe
{
    /**
     * @param array<string, mixed> $engineSettings Effective engine.* settings
     * @return array{
     *     deploymentMode: array{configured: string, active: string, status: string},
     *     storageDriver: array{configured: string, active: string, status: string},
     *     capabilities: array<string, array{status: string, message: string}>
     * }
     */
    public function probe(StorageInterface $storage, array $engineSettings): array
    {
        $configuredMode = (string) ($engineSettings['deploymentMode'] ?? StorageFactory::DEFAULT_DEPLOYMENT_MODE);
        $configuredDriver = (string) ($engineSettings['storageDriver'] ?? StorageFactory::DEFAULT_DRIVER);
        $activeMode = StorageFactory::deploymentModeFromEngineSettings($engineSettings);
        $activeDriver = StorageFactory::driverFromEngineSettings($engineSettings);

        $localWritable = $this->probeLocalWritable($storage);

        return [
            'deploymentMode' => [
                'configured' => $configuredMode,
                'active' => $activeMode,
                'status' => $configuredMode === $activeMode ? 'active' : 'fallback',
            ],
            'storageDriver' => [
                'configured' => $configuredDriver,
                'active' => $activeDriver,
                'status' => $configuredDriver === $activeDriver ? 'active' : 'fallback',
            ],
            'capabilities' => [
                'localStorage' => [
                    'status' => $localWritable ? 'available' : 'failing',
                    'message' => $localWritable
                        ? 'Local flat-file storage is operational.'
                        : 'Local storage root is not writable.',
                ],
                'classicMode' => [
                    'status' => 'available',
                    'message' => 'Classic deployment mode is active.',
                ],
                'hybridMode' => [
                    'status' => 'unavailable',
                    'message' => 'Hybrid mode is not installed in this release.',
                ],
                'gitHeadlessMode' => [
                    'status' => 'unavailable',
                    'message' => 'Git headless mode is not installed in this release.',
                ],
                'remoteStorageDrivers' => [
                    'status' => 'unavailable',
                    'message' => 'Only the local driver is available in Iteration 68.',
                ],
                'schemaValidation' => [
                    'status' => ($engineSettings['schemaValidationEnabled'] ?? true) ? 'available' : 'disabled',
                    'message' => 'JSON Schema validation for admin documents.',
                ],
            ],
        ];
    }

    private function probeLocalWritable(StorageInterface $storage): bool
    {
        $base = $storage->getBasePath();

        return is_dir($base) && is_writable($base);
    }
}
