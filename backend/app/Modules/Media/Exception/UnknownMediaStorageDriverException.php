<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Exception;

use RuntimeException;

final class UnknownMediaStorageDriverException extends RuntimeException
{
    public function __construct(string $driver)
    {
        parent::__construct('Unknown media storage driver: ' . $driver);
    }
}
