<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Contracts;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;

/**
 * Rozhranie pre správu autorizácie (RBAC).
 */
interface AuthorizationInterface
{
    /** Role používateľov */
    public const ROLE_SUPER_ADMIN = 'SUPER_ADMIN';
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_EDITOR = 'EDITOR';
    public const ROLE_USER = 'USER';

    /**
     * Overí, či má používateľ požadovanú rolu.
     *
     * @param User $user Používateľ.
     * @param string|array<int, string> $roles Požadovaná rola alebo zoznam rolí.
     * @return bool TRUE ak má požadovanú rolu.
     */
    public function hasRole(User $user, string|array $roles): bool;

    /**
     * Overí, či má používateľ požadované oprávnenie.
     *
     * @param User $user Používateľ.
     * @param string $permission Požadované oprávnenie.
     * @return bool TRUE ak má oprávnenie.
     */
    public function hasPermission(User $user, string $permission): bool;

    /**
     * Získa zoznam rolí používateľa.
     *
     * @param User $user Používateľ.
     * @return array<int, string> Zoznam rolí.
 */public function getRoles(User $user): array;

    /**
     * Pridá rolu používateľovi.
     *
     * @param User $user Používateľ.
     * @param string $role Rola.
     * @throws AuthorizationException Ak rola neexistuje.
     */
    public function addRole(User $user, string $role): void;

    /**
     * Odstráni rolu používateľovi.
     *
     * @param User $user Používateľ.
     * @param string $role Rola.
     */
    public function removeRole(User $user, string $role): void;

    /**
     * Kontrola prístupu – vyhodí výnimku ak nemá prístup.
     *
     * @param User $user Používateľ.
     * @param string|array<int, string> $roles Požadovaná rola.
     * @throws AuthorizationException Ak nemá prístup.
     */
    public function requireRole(User $user, string|array $roles): void;

    /**
     * Kontrola prístupu – vyhodí výnimku ak nemá oprávnenie.
     *
     * @param User $user Používateľ.
     * @param string $permission Požadované oprávnenie.
     * @throws AuthorizationException Ak nemá oprávnenie.
     */
    public function requirePermission(User $user, string $permission): void;
}
