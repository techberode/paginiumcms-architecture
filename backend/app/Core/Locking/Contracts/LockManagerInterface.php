<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Locking\Contracts;

use PaginiumCMS\Core\Locking\Exception\LockConflictException;
use PaginiumCMS\Core\Locking\Models\ContentLock;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * === Kontrakt: LockManagerInterface ===
 * Definuje správu zámkov dokumentov nad flat-file registrom.
 * Žiadna databáza – stav je v `data/locks.json`, prístup je serializovaný cez flock.
 */
interface LockManagerInterface
{
    /**
     * Získa zámok pre daný zdroj. Ak už zdroj drží iný používateľ, vyhodí konflikt.
     * Ak zdroj drží ten istý používateľ, zámok sa obnoví (re-acquire).
     *
     * @throws LockConflictException Ak je zdroj uzamknutý iným používateľom.
     */
    public function acquire(string $resourceId, User $user): ContentLock;

    /**
     * Obnoví (predĺži) zámok – volané frontendom periodicky (heartbeat).
     *
     * @throws LockConflictException Ak zámok neexistuje, expiroval alebo token nesedí.
     */
    public function heartbeat(string $resourceId, string $token): ContentLock;

    /**
     * Uvoľní zámok vlastníka (token musí sedieť).
     */
    public function release(string $resourceId, string $token): void;

    /**
     * Vráti aktuálny (neexpirovaný) zámok pre zdroj, alebo null.
     */
    public function getLock(string $resourceId): ?ContentLock;

    /**
     * Vráti všetky aktuálne aktívne zámky (pre admin dashboard).
     *
     * @return array<int, ContentLock>
     */
    public function getAllLocks(): array;

    /**
     * Administratívne vynútené uvoľnenie zámku (bez tokenu).
     */
    public function forceRelease(string $resourceId): void;

    /**
     * Odstráni expirované zámky (auto-release). Vráti počet uvoľnených.
     */
    public function purgeExpired(): int;
}
