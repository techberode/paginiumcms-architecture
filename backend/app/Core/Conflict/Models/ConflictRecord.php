<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Conflict\Models;

use JsonSerializable;

/**
 * === Model: ConflictRecord ===
 * Záznam o zachytenom konflikte obsahu (HTTP 409) pre admin prehľad a audit.
 * Ukladá sa do flat-file logu `data/conflicts.json` (Iterácia 3).
 */
final class ConflictRecord implements JsonSerializable
{
    public function __construct(
        private string $resourceId,
        private string $userId,
        private string $userName,
        private string $baseRevision,
        private string $serverRevision,
        private int $occurredAt
    ) {
    }

    public static function create(
        string $resourceId,
        string $userId,
        string $userName,
        string $baseRevision,
        string $serverRevision
    ): self {
        return new self($resourceId, $userId, $userName, $baseRevision, $serverRevision, time());
    }

    /**
     * @param array<int|string, mixed> $data
 */public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['resourceId'] ?? ''),
            (string) ($data['userId'] ?? ''),
            (string) ($data['userName'] ?? ''),
            (string) ($data['baseRevision'] ?? ''),
            (string) ($data['serverRevision'] ?? ''),
            (int) ($data['occurredAt'] ?? 0)
        );
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getOccurredAt(): int
    {
        return $this->occurredAt;
    }

    /**
     * @return array<int|string, mixed>
 */public function jsonSerialize(): array
    {
        return [
            'resourceId' => $this->resourceId,
            'userId' => $this->userId,
            'userName' => $this->userName,
            'baseRevision' => $this->baseRevision,
            'serverRevision' => $this->serverRevision,
            'occurredAt' => $this->occurredAt,
        ];
    }
}
