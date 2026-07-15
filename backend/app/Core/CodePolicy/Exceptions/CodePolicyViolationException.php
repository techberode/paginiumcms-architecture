<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Exceptions;

use RuntimeException;

/**
 * Code policy violation (Iteration 14) – mapped to HTTP 422 by ApiErrorHandler.
 */
final class CodePolicyViolationException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        private array $errors,
        string $message = 'Code policy validation failed',
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
