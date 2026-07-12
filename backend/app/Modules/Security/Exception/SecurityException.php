<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Exception;

/**
 * Základná výnimka pre Security modul.
 */
class SecurityException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
