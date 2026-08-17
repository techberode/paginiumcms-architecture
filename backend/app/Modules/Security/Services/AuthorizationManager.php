<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\PermissionCatalog;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Implementácia správy autorizácie (RBAC).
 */
class AuthorizationManager implements AuthorizationInterface
{
    /** @var array<string, array<int, string>> Mapovanie rolí na oprávnenia */
    private array $rolePermissions = [];

    public function __construct(
        private ?SecurityAuditStore $securityAudit = null,
        private ?SettingsRepositoryInterface $settings = null,
        private ?RoleRepository $roles = null,
    ) {
        $this->rolePermissions = PermissionCatalog::defaultRolePermissions();
        $this->reloadFromSettings();
        $this->reloadFromRoles();
    }

    public function reloadFromSettings(): void
    {
        if ($this->settings === null) {
            return;
        }

        $accessControl = $this->settings->group('accessControl');

        foreach (PermissionCatalog::configurableRoles() as $role) {
            $key = PermissionCatalog::settingsKeyForRole($role);
            $encoded = (string) ($accessControl[$key] ?? '');

            if ($encoded === '') {
                continue;
            }

            $permissions = PermissionCatalog::normalizeList(
                PermissionCatalog::decodePermissions($encoded)
            );
            $permissions = $this->applyRoleGuards($role, $permissions);

            if ($permissions !== []) {
                $this->rolePermissions[$role] = $permissions;
            }
        }
    }

    public function reloadFromRoles(): void
    {
        if ($this->roles === null) {
            return;
        }

        foreach ($this->roles->permissionsMap() as $role => $permissions) {
            $permissions = $this->applyRoleGuards($role, $permissions);

            if ($permissions !== []) {
                $this->rolePermissions[$role] = $permissions;
            }
        }
    }

    public function hasRole(User $user, string|array $roles): bool
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

            // content:manage / media:manage pokrývajú všetky akcie v doméne
            foreach ($permissions as $perm) {
                if (!str_ends_with($perm, ':manage')) {
                    continue;
                }
                $resource = substr($perm, 0, -strlen(':manage'));
                if (str_starts_with($permission, $resource . ':')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getRoles(User $user): array
    {
        return $user->getRoles();
    }

    public function addRole(User $user, string $role): void
    {
        if (!isset($this->rolePermissions[$role]) && $role !== self::ROLE_SUPER_ADMIN) {
            throw new AuthorizationException(sprintf('Rola "%s" neexistuje', $role));
        }

        $user->addRole($role);
    }

    public function removeRole(User $user, string $role): void
    {
        $user->removeRole($role);
    }

    public function requireRole(User $user, string|array $roles): void
    {
        if (!$this->hasRole($user, $roles)) {
            $roleList = is_array($roles) ? implode(', ', $roles) : $roles;
            $this->securityAudit?->append(
                'role_denied',
                'WARNING',
                sprintf('Required role denied: %s', $roleList),
                $user->getId(),
                $user->getEmail()
            );
            throw new AuthorizationException(sprintf('Vyžaduje sa rola: %s', $roleList));
        }
    }

    public function requirePermission(User $user, string $permission): void
    {
        if (!$this->hasPermission($user, $permission)) {
            $this->securityAudit?->append(
                'permission_denied',
                'WARNING',
                sprintf('Permission denied: %s', $permission),
                $user->getId(),
                $user->getEmail(),
                null,
                ['permission' => $permission]
            );
            throw new AuthorizationException(sprintf('Nedostatočné oprávnenie: %s', $permission));
        }
    }

    /**
     * Pridá alebo aktualizuje mapovanie rolí na oprávnenia.
     *
     * @param string $role Rola.
     * @param array<int, string> $permissions Zoznam oprávnení.
 */public function setRolePermissions(string $role, array $permissions): void
    {
        $this->rolePermissions[$role] = $permissions;
    }

    /**
     * Získa oprávnenia pre rolu.
     *
     * @param string $role Rola.
     * @return array<int, string> Zoznam oprávnení.
 */public function getRolePermissions(string $role): array
    {
        return $this->rolePermissions[$role] ?? [];
    }

    /**
     * @param list<string> $permissions
     * @return list<string>
     */
    private function applyRoleGuards(string $role, array $permissions): array
    {
        if ($role === AuthorizationInterface::ROLE_ADMIN
            && !in_array('api-keys:manage', $permissions, true)
        ) {
            $permissions[] = 'api-keys:manage';
            $permissions = PermissionCatalog::normalizeList($permissions);
        }

        if ($role === AuthorizationInterface::ROLE_ADMIN
            && !in_array('redirects:manage', $permissions, true)
        ) {
            $permissions[] = 'redirects:manage';
            $permissions = PermissionCatalog::normalizeList($permissions);
        }

        if ($role === AuthorizationInterface::ROLE_ADMIN
            && !in_array('webhooks:manage', $permissions, true)
        ) {
            $permissions[] = 'webhooks:manage';
            $permissions = PermissionCatalog::normalizeList($permissions);
        }

        if ($role === AuthorizationInterface::ROLE_ADMIN
            && !in_array('metrics:read', $permissions, true)
        ) {
            $permissions[] = 'metrics:read';
            $permissions = PermissionCatalog::normalizeList($permissions);
        }

        if ($role === AuthorizationInterface::ROLE_ADMIN
            && !$this->hasContentDomainPermission($permissions)
        ) {
            $permissions[] = 'content:manage';
            $permissions = PermissionCatalog::normalizeList($permissions);
        }

        if ($role === AuthorizationInterface::ROLE_EDITOR
            && in_array('content:create', $permissions, true)
            && !$this->hasContentEditPermission($permissions)
        ) {
            $permissions[] = 'content:edit';
            $permissions = PermissionCatalog::normalizeList($permissions);
        }

        return $permissions;
    }

    /**
     * @param list<string> $permissions
     */
    private function hasContentDomainPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($permission === 'content:manage' || str_starts_with($permission, 'content:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $permissions
     */
    private function hasContentEditPermission(array $permissions): bool
    {
        if (in_array('content:manage', $permissions, true)
            || in_array('content:edit', $permissions, true)
        ) {
            return true;
        }

        return false;
    }
}
