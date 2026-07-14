<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Exception;

/**
 * Základná výnimka pre FlatFile modul.
 */
class FlatFileException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
