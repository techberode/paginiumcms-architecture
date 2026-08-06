<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;
use PaginiumCMS\Modules\Media\Exception\UnknownMediaStorageDriverException;

/**
 * Allow-listed media binary driver resolver (Iteration 72).
 */
final class MediaStorageFactory
{
    /** @var list<string> */
    public const ALLOWED_DRIVERS = ['local', 's3'];

    /** @var list<string> */
    public const ACTIVE_DRIVERS = ['local'];

    public const DEFAULT_DRIVER = 'local';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
    ) {
    }

    public function create(?string $driver = null, bool $allowFallback = true): MediaStorageDriverInterface
    {
        $requested = $driver ?? self::DEFAULT_DRIVER;

        if ($allowFallback) {
            $normalized = self::normalizeDriver($requested);
        } elseif (!in_array($requested, self::ACTIVE_DRIVERS, true)) {
            throw new UnknownMediaStorageDriverException($requested);
        } else {
            $normalized = $requested;
        }

        return match ($normalized) {
            'local' => new LocalMediaStorageDriver($this->reader, $this->writer),
            default => throw new UnknownMediaStorageDriverException($requested),
        };
    }

    public static function normalizeDriver(string $driver): string
    {
        $driver = strtolower(trim($driver));

        if ($driver === 's3') {
            return self::DEFAULT_DRIVER;
        }

        if (!in_array($driver, self::ACTIVE_DRIVERS, true)) {
            return self::DEFAULT_DRIVER;
        }

        return $driver;
    }

    /**
     * @param array<string, mixed> $mediaGroup
     */
    public static function driverFromMediaSettings(array $mediaGroup): string
    {
        $driver = (string) ($mediaGroup['storageDriver'] ?? self::DEFAULT_DRIVER);

        return self::normalizeDriver($driver);
    }
}
