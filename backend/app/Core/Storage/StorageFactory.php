<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Storage;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Core\Storage\Drivers\LocalFlatFileStorage;
use PaginiumCMS\Core\Storage\Exception\UnknownStorageDriverException;

/**
 * Resolves allow-listed storage drivers with safe Classic/local defaults.
 */
final class StorageFactory
{
    /** @var list<string> */
    public const ALLOWED_DRIVERS = ['local'];

    public const DEFAULT_DRIVER = 'local';

    public const DEFAULT_DEPLOYMENT_MODE = 'classic';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private FileValidator $validator,
    ) {
    }

    public function create(?string $driver = null, bool $allowFallback = true): StorageInterface
    {
        $requested = $driver ?? self::DEFAULT_DRIVER;

        if ($allowFallback) {
            $normalized = self::normalizeDriver($requested);
        } elseif (!in_array($requested, self::ALLOWED_DRIVERS, true)) {
            throw new UnknownStorageDriverException($requested);
        } else {
            $normalized = $requested;
        }

        return match ($normalized) {
            'local' => new LocalFlatFileStorage($this->reader, $this->writer, $this->validator),
            default => throw new UnknownStorageDriverException($requested),
        };
    }

    /**
     * Bootstrap-safe driver resolution — unknown values fall back to local.
     */
    public static function normalizeDriver(string $driver): string
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
        $driver = (string) ($engineGroup['storageDriver'] ?? self::DEFAULT_DRIVER);

        return self::normalizeDriver($driver);
    }

    /**
     * @param array<string, mixed> $engineGroup
     */
    public static function deploymentModeFromEngineSettings(array $engineGroup): string
    {
        $mode = (string) ($engineGroup['deploymentMode'] ?? self::DEFAULT_DEPLOYMENT_MODE);
        $allowed = ['classic', 'hybrid', 'git_headless'];

        if (!in_array($mode, $allowed, true)) {
            return self::DEFAULT_DEPLOYMENT_MODE;
        }

        // It.68: only classic is active; others are stored but not activated.
        if ($mode !== self::DEFAULT_DEPLOYMENT_MODE) {
            return self::DEFAULT_DEPLOYMENT_MODE;
        }

        return $mode;
    }
}
