<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Exception;

use RuntimeException;

/**
 * Translation file failed policy validation (It.19).
 *
 * @phpstan-type TranslationPolicyError array{
 *     code: string,
 *     message: string,
 *     line?: int,
 *     hint?: string
 * }
 */
final class TranslationPolicyViolationException extends RuntimeException
{
    /** @param list<TranslationPolicyError> $errors */
    public function __construct(
        private array $errors,
        private ?string $rejectedPath = null
    ) {
        $first = $errors[0]['message'] ?? 'Translation policy violation';
        parent::__construct($first);
    }

    /**
     * @return list<TranslationPolicyError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getRejectedPath(): ?string
    {
        return $this->rejectedPath;
    }
}
