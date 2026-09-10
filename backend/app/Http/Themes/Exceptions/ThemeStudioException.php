<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Fail-closed Theme Studio read errors (It.88a) with an HTTP status for the controller.
 */
final class ThemeStudioException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
