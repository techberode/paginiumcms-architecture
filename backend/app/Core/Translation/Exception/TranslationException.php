<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Exception;

use RuntimeException;

/**
 * Domain error for assisted translation (It.76). Never includes provider payloads or secrets.
 */
final class TranslationException extends RuntimeException
{
    /**
     * @param array<string, mixed>|null $context Safe for JSON (no secrets); e.g. locale rows on NO_RESULT.
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 422,
        public readonly string $errorCode = 'INVALID',
        public readonly ?array $context = null,
    ) {
        parent::__construct($message);
    }
}
