<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Locking\Models;

use JsonSerializable;

/**
 * === Model: ContentLock ===
 * Reprezentuje jeden zámok nad dokumentom (stránka, článok, asset).
 * Stav sa neukladá do DB, ale do flat-file registra `data/locks.json`.
 *
 * Anatómia:
 *  - resourceId    : unikátny identifikátor zdroja (napr. "page:o-nas", "media:foto.jpg")
 *  - lockedBy      : ID používateľa, ktorý zámok drží
 *  - lockedByName  : zobrazované meno (pre LockIndicator na frontende)
 *  - token         : tajný token vlastníka – vyžadovaný pri heartbeat/release (ochrana proti cudziemu uvoľneniu)
 *  - acquiredAt    : čas získania zámku (unix timestamp)
 *  - lastHeartbeat : čas posledného heartbeatu (unix timestamp)
 *  - expiresAt     : čas expirácie = lastHeartbeat + TTL (auto-release)
 */
final class ContentLock implements JsonSerializable
{
    public function __construct(
        private string $resourceId,
        private string $lockedBy,
        private string $lockedByName,
        private string $token,
        private int $acquiredAt,
        private int $lastHeartbeat,
        private int $expiresAt
    ) {
    }

    // === Blok: Factory metódy ===

    /**
     * Vytvorí nový zámok pre daného vlastníka.
     */
    public static function create(
        string $resourceId,
        string $lockedBy,
        string $lockedByName,
        int $ttl,
        ?string $token = null,
        ?int $now = null
    ): self {
        $now ??= time();
        $token ??= bin2hex(random_bytes(16));

        return new self(
            $resourceId,
            $lockedBy,
            $lockedByName,
            $token,
            $now,
            $now,
            $now + $ttl
        );
    }

    /**
     * Rekonštruuje zámok zo záznamu v `locks.json`.
     *
     * @param array<int|string, mixed> $data
 */public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['resourceId'] ?? ''),
            (string) ($data['lockedBy'] ?? ''),
            (string) ($data['lockedByName'] ?? ''),
            (string) ($data['token'] ?? ''),
            (int) ($data['acquiredAt'] ?? 0),
            (int) ($data['lastHeartbeat'] ?? 0),
            (int) ($data['expiresAt'] ?? 0)
        );
    }

    // === Blok: Správanie zámku ===

    /**
     * Posunie heartbeat a expiráciu (predĺženie životnosti zámku).
     */
    public function touch(int $ttl, ?int $now = null): void
    {
        $now ??= time();
        $this->lastHeartbeat = $now;
        $this->expiresAt = $now + $ttl;
    }

    /**
     * Zistí, či zámok expiroval (auto-release po TTL bez heartbeatu).
     */
    public function isExpired(?int $now = null): bool
    {
        $now ??= time();

        return $now >= $this->expiresAt;
    }

    /**
     * Overí, či token zodpovedá vlastníkovi zámku (timing-safe).
     */
    public function ownsToken(string $token): bool
    {
        return $this->token !== '' && hash_equals($this->token, $token);
    }

    /**
     * Overí, či zámok patrí danému používateľovi.
     */
    public function isOwnedBy(string $userId): bool
    {
        return $this->lockedBy === $userId;
    }

    // === Blok: Gettery ===

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getLockedBy(): string
    {
        return $this->lockedBy;
    }

    public function getLockedByName(): string
    {
        return $this->lockedByName;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getAcquiredAt(): int
    {
        return $this->acquiredAt;
    }

    public function getLastHeartbeat(): int
    {
        return $this->lastHeartbeat;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    // === Blok: Serializácia ===

    /**
     * Plná reprezentácia vrátane tokenu – LEN pre uloženie do `locks.json`.
     *
     * @return array<int|string, mixed>
 */public function toArray(): array
    {
        return [
            'resourceId' => $this->resourceId,
            'lockedBy' => $this->lockedBy,
            'lockedByName' => $this->lockedByName,
            'token' => $this->token,
            'acquiredAt' => $this->acquiredAt,
            'lastHeartbeat' => $this->lastHeartbeat,
            'expiresAt' => $this->expiresAt,
        ];
    }

    /**
     * Bezpečná reprezentácia pre API odpoveď – BEZ tokenu.
     * Token sa nikdy neposiela iným klientom (len vlastníkovi pri acquire).
     *
     * @return array<int|string, mixed>
 */public function jsonSerialize(): array
    {
        return [
            'resourceId' => $this->resourceId,
            'lockedBy' => $this->lockedBy,
            'lockedByName' => $this->lockedByName,
            'acquiredAt' => $this->acquiredAt,
            'lastHeartbeat' => $this->lastHeartbeat,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
