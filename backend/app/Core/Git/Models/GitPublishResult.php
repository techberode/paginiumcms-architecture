<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Models;

/**
 * Outcome of a Git distribution step (Iteration 70).
 */
final class GitPublishResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $state,
        public readonly ?string $commitHash = null,
        public readonly ?string $message = null,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'state' => $this->state,
            'commitHash' => $this->commitHash,
            'message' => $this->message,
            'error' => $this->error,
        ];
    }
}
