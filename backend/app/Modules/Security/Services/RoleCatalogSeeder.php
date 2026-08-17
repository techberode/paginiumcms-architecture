<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\PermissionCatalog;

/**
 * Seeds and migrates system RBAC roles into data/roles.json (It.84d).
 */
final class RoleCatalogSeeder
{
    /** @var array<string, string> */
    private const SYSTEM_ROLE_LABELS = [
        'ADMIN' => 'Administrator',
        'EDITOR' => 'Editor',
        'USER' => 'User',
    ];

    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    public function seedIfEmpty(?SettingsRepositoryInterface $settings = null): void
    {
        if ($this->roles->list() !== []) {
            return;
        }

        $accessControl = $settings?->group('accessControl') ?? [];

        foreach (RoleRepository::SYSTEM_ROLE_IDS as $roleId) {
            $permissions = $this->permissionsForSystemRole($roleId, $accessControl);
            $this->roles->save(
                $roleId,
                self::SYSTEM_ROLE_LABELS[$roleId],
                $permissions,
                true,
            );
        }
    }

    /**
     * @param array<string, mixed> $accessControl
     */
    public function syncSystemRolesFromSettings(array $accessControl): void
    {
        foreach (RoleRepository::SYSTEM_ROLE_IDS as $roleId) {
            $permissions = $this->permissionsForSystemRole($roleId, $accessControl);
            $existing = $this->roles->get($roleId);
            $label = $existing !== null ? $existing->name : self::SYSTEM_ROLE_LABELS[$roleId];

            $this->roles->save($roleId, $label, $permissions, true);
        }
    }

    /**
     * @param array<string, mixed> $accessControl
     * @return list<string>
     */
    private function permissionsForSystemRole(string $roleId, array $accessControl): array
    {
        $defaults = PermissionCatalog::defaultRolePermissions()[$roleId] ?? [];
        $key = PermissionCatalog::settingsKeyForRole($roleId);
        $encoded = (string) ($accessControl[$key] ?? '');

        if ($encoded === '') {
            return $defaults;
        }

        $fromSettings = PermissionCatalog::normalizeList(
            PermissionCatalog::decodePermissions($encoded)
        );

        return $fromSettings !== [] ? $fromSettings : $defaults;
    }
}
