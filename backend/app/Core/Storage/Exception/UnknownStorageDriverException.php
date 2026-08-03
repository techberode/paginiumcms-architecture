<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Storage\Exception;

/**
 * Raised when a storage driver is not on the allow-list (Iteration 68).
 */
final class UnknownStorageDriverException extends StorageException
{
    public function __construct(string $driver)
    {
        parent::__construct(sprintf('Unknown or disallowed storage driver: %s', $driver));
    }
}
