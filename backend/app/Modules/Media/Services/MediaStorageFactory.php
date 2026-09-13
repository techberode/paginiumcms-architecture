<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;
use PaginiumCMS\Modules\Media\Contracts\S3MediaFilesystemFactoryInterface;
use PaginiumCMS\Modules\Media\Exception\UnknownMediaStorageDriverException;

/**
 * Allow-listed media binary driver resolver (Iteration 72).
 */
final class MediaStorageFactory
{
    /** @var list<string> */
    public const ALLOWED_DRIVERS = ['local', 's3'];

    /** @var list<string> */
    public const ACTIVE_DRIVERS = ['local', 's3'];

    public const DEFAULT_DRIVER = 'local';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private SettingsRepositoryInterface $settings,
        private OutboundUrlGuard $outboundUrlGuard,
        private S3MediaFilesystemFactoryInterface $s3FilesystemFactory,
        private MediaUrlResolver $urlResolver,
    ) {
    }

    /**
     * @param array<string, mixed>|null $mediaSettings
     */
    public function create(
        ?string $driver = null,
        bool $allowFallback = true,
        ?array $mediaSettings = null,
    ): MediaStorageDriverInterface {
        $mediaSettings ??= $this->settings->group('media');
        $requested = strtolower(trim($driver ?? (string) ($mediaSettings['storageDriver'] ?? self::DEFAULT_DRIVER)));

        if ($requested === 's3') {
            return $this->createS3OrFallback($mediaSettings, $allowFallback);
        }

        if ($allowFallback) {
            $normalized = self::normalizeDriver($requested, $mediaSettings, $this->outboundUrlGuard);
        } elseif (!in_array($requested, self::ACTIVE_DRIVERS, true)) {
            throw new UnknownMediaStorageDriverException($requested);
        } else {
            $normalized = $requested;
        }

        return match ($normalized) {
            'local' => new LocalMediaStorageDriver($this->reader, $this->writer, $this->urlResolver),
            's3' => $this->createS3Driver($mediaSettings),
            default => throw new UnknownMediaStorageDriverException($requested),
        };
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    public function resolveActiveDriver(array $mediaSettings): string
    {
        $configured = strtolower(trim((string) ($mediaSettings['storageDriver'] ?? self::DEFAULT_DRIVER)));

        return self::normalizeDriver($configured, $mediaSettings, $this->outboundUrlGuard);
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    public static function driverFromMediaSettings(array $mediaSettings, ?OutboundUrlGuard $guard = null): string
    {
        $configured = strtolower(trim((string) ($mediaSettings['storageDriver'] ?? self::DEFAULT_DRIVER)));

        return self::normalizeDriver($configured, $mediaSettings, $guard ?? OutboundUrlGuard::fromEnv());
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    public static function normalizeDriver(
        string $driver,
        array $mediaSettings = [],
        ?OutboundUrlGuard $guard = null,
    ): string {
        $driver = strtolower(trim($driver));

        if ($driver === 's3') {
            $config = S3MediaStorageConfig::tryFromSettings($mediaSettings, $guard ?? OutboundUrlGuard::fromEnv());
            if ($config !== null && $config->isComplete()) {
                return 's3';
            }

            return self::DEFAULT_DRIVER;
        }

        if (!in_array($driver, self::ACTIVE_DRIVERS, true)) {
            return self::DEFAULT_DRIVER;
        }

        return $driver === 'local' ? 'local' : self::DEFAULT_DRIVER;
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    private function createS3OrFallback(array $mediaSettings, bool $allowFallback): MediaStorageDriverInterface
    {
        try {
            return $this->createS3Driver($mediaSettings);
        } catch (\Throwable) {
            if (!$allowFallback) {
                throw new UnknownMediaStorageDriverException('s3');
            }

            return new LocalMediaStorageDriver($this->reader, $this->writer, $this->urlResolver);
        }
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    private function createS3Driver(array $mediaSettings): MediaStorageDriverInterface
    {
        $config = S3MediaStorageConfig::fromSettings($mediaSettings, $this->outboundUrlGuard);
        $filesystem = $this->s3FilesystemFactory->create($config);

        return new S3MediaStorageDriver($filesystem, $config, $this->urlResolver);
    }
}
