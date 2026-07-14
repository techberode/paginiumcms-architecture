<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

use RuntimeException;

/**
 * === Výnimka: ValidationException ===
 * Jednotná výnimka pre zlyhanie validácie (Iterácia 4).
 *
 * Nesie mapu chýb po jednotlivých poliach (`field => [správy]`), ktorú
 * jednotný Error Handler (ApiErrorHandler) mapuje na HTTP 422 s obalom
 * `{ success: false, error, errors }`. Frontend tak vie zvýrazniť konkrétne
 * polia bez ďalšieho parsovania.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $errors Mapa chýb podľa poľa.
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
     * @return list<string> Ploché pole všetkých chybových správ.
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
