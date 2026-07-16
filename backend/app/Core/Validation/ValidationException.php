<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

use RuntimeException;

/**
 * Jednotná výnimka pre zlyhanie validácie (Iterácia 4).
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        private array $errors,
        string $message = 'Validácia zlyhala',
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

    /**
     * @return list<string>
     */
    public function getFlatMessages(): array
    {
        $flat = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $flat[] = $message;
            }
        }

        return $flat;
    }
}
