<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;

/**
 * Implementácia správy autorizácie (RBAC).
 */
class AuthorizationManager implements AuthorizationInterface
{
    /** @var array<string, array<int, string>> Mapovanie rolí na oprávnenia */
    private array $rolePermissions = [];

    public function __construct()
    {
        // Predvolené mapovanie rolí na oprávnenia
        $this->rolePermissions = [
            self::ROLE_SUPER_ADMIN => ['*'],
            self::ROLE_ADMIN => [
                'user:manage', 'content:manage', 'media:manage', 'settings:manage', 'logs:view'
            ],
            self::ROLE_EDITOR => [
                'content:create', 'content:edit', 'content:delete', 'media:upload', 'media:delete'
            ],
            self::ROLE_USER => [
                'content:view', 'profile:edit'
            ],
        ];
    }

    public function hasRole(User $user, $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $userRoles = $user->getRoles();

        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(User $user, string $permission): bool
    {
        // Super admin má všetko
        if (in_array('SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $userRoles = $user->getRoles();

        foreach ($userRoles as $role) {
            $permissions = $this->rolePermissions[$role] ?? [];
            
            // Ak má rola '*' (všetko), má prístup
            if (in_array('*', $permissions, true)) {
                return true;
            }
            
            // Kontrola konkrétneho oprávnenia
            if (in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function getRoles(User $user): array
    {
        return $user->getRoles();
    }

    public function addRole(User $user, string $role): void
    {
        if (!isset($this->rolePermissions[$role])) {
            throw new AuthorizationException(sprintf('Rola "%s" neexistuje', $role));
        }

        $user->addRole($role);
    }

    public function removeRole(User $user, string $role): void
    {
        $user->removeRole($role);
    }

    public function requireRole(User $user, $roles): void
    {
        if (!$this->hasRole($user, $roles)) {
            $roleList = is_array($roles) ? implode(', ', $roles) : $roles;
            throw new AuthorizationException(sprintf('Vyžaduje sa rola: %s', $roleList));
        }
    }

    public function requirePermission(User $user, string $permission): void
    {
        if (!$this->hasPermission($user, $permission)) {
            throw new AuthorizationException(sprintf('Nedostatočné oprávnenie: %s', $permission));
        }
    }

    /**
     * Pridá alebo aktualizuje mapovanie rolí na oprávnenia.
     *
     * @param string $role Rola.
     * @param array<int, string> $permissions Zoznam oprávnení.
     */
    public function setRolePermissions(string $role, array $permissions): void
    {
        $this->rolePermissions[$role] = $permissions;
    }

    /**
     * Získa oprávnenia pre rolu.
     *
     * @param string $role Rola.
     * @return array<int, string> Zoznam oprávnení.
     */
    public function getRolePermissions(string $role): array
    {
        return $this->rolePermissions[$role] ?? [];
    }
}
