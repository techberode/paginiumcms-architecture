<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Models;

/**
 * Outcome of a single job handler execution (Iteration 29).
 */
final class JobRunResult
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $data = [],
        public readonly ?string $reason = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'reason' => $this->reason,
            'data' => $this->data,
        ];
    }
}
