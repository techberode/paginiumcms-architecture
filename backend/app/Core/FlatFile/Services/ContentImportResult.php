<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

/**
 * Summary of a content import run (It.80f / 80g).
 */
final class ContentImportResult
{
    public function __construct(
        public int $created = 0,
        public int $skipped = 0,
        /** @var list<string> */
        public array $messages = [],
        /** @var list<string> */
        public array $errors = [],
    ) {
    }

    public function addCreated(string $message): void
    {
        ++$this->created;
        $this->messages[] = $message;
    }

    public function addSkipped(string $message): void
    {
        ++$this->skipped;
        $this->messages[] = $message;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function isSuccessful(): bool
    {
        return $this->errors === [];
    }
}
