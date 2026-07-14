<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Exception;

/**
 * Výnimka pre chyby pri dvojfaktorovej autentifikácii.
 */
class TwoFactorException extends SecurityException
{
    public function __construct(string $message = 'Dvojfaktorová autentifikácia zlyhala', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
