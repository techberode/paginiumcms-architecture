<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

use RuntimeException;

final class HostMetricsIngestException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $statusCode
    ) {
        parent::__construct($errorCode);
    }
}
