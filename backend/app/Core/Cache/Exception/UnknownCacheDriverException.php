<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Exception;

use RuntimeException;

final class UnknownCacheDriverException extends RuntimeException
{
    public function __construct(string $driver)
    {
        parent::__construct('Unknown or inactive cache driver: ' . $driver);
    }
}
