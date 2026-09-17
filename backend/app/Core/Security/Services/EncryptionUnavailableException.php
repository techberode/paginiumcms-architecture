<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use RuntimeException;

/**
 * Thrown when at-rest encryption is required but APP_KEY is missing or invalid.
 */
final class EncryptionUnavailableException extends RuntimeException
{
    public static function forMissingAppKey(): self
    {
        return new self(
            'At-rest encryption is disabled: set a valid 32-byte APP_KEY in the environment before storing secrets.'
        );
    }

    public static function forCipherFailure(): self
    {
        return new self('At-rest encryption failed: OpenSSL could not encrypt the value.');
    }
}
