<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Exception;

/**
 * Výnimka pre chyby pri autorizácii.
 */
class AuthorizationException extends SecurityException
{
    public function __construct(string $message = 'Nedostatočné oprávnenia', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
