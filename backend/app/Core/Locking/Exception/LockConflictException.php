<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Locking\Exception;

use PaginiumCMS\Core\Locking\Models\ContentLock;
use RuntimeException;

/**
 * === Výnimka: LockConflictException ===
 * Vyhodená, keď sa používateľ pokúsi získať alebo upraviť zámok,
 * ktorý už drží niekto iný. Mapuje sa na HTTP 409 Conflict.
 */
final class LockConflictException extends RuntimeException
{
    public function __construct(private ContentLock $currentLock, string $message = '')
    {
        if ($message === '') {
            $message = sprintf(
                'Zdroj "%s" je uzamknutý používateľom %s.',
                $currentLock->getResourceId(),
                $currentLock->getLockedByName()
            );
        }

        parent::__construct($message, 409);
    }

    /**
     * Aktuálne platný zámok, ktorý spôsobil konflikt.
     */
    public function getCurrentLock(): ContentLock
    {
        return $this->currentLock;
    }
}
