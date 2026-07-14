<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Exception;

/**
 * Výnimka pre chyby pri autentifikácii.
 */
class AuthenticationException extends SecurityException
{
    public function __construct(string $message = 'Autentifikácia zlyhala', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
