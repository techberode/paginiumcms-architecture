<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Models;

/**
 * Idempotent Git publish queue entry (Iteration 70).
 */
final class PublishQueueItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $resourcePath,
        public readonly string $fingerprint,
        public readonly string $action,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $committedAt = null,
        public readonly ?string $commitHash = null,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['resourcePath'] ?? ''),
            (string) ($data['fingerprint'] ?? ''),
            (string) ($data['action'] ?? 'upsert'),
            (string) ($data['status'] ?? 'pending_publish'),
            (string) ($data['createdAt'] ?? ''),
            isset($data['committedAt']) ? (string) $data['committedAt'] : null,
            isset($data['commitHash']) ? (string) $data['commitHash'] : null,
            isset($data['error']) ? (string) $data['error'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'resourcePath' => $this->resourcePath,
            'fingerprint' => $this->fingerprint,
            'action' => $this->action,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'committedAt' => $this->committedAt,
            'commitHash' => $this->commitHash,
            'error' => $this->error,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
