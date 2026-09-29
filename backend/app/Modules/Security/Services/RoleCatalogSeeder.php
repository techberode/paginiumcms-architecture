<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Editor\Services\ExternalEmbedContentService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
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
            $this->backfillSystemRoleDefaults();

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

        $this->backfillSystemRoleDefaults();
    }

    /**
     * Non-destructive: append newly shipped default permissions to existing system roles.
     */
    public function backfillSystemRoleDefaults(): void
    {
        /** @var array<string, list<string>> $append */
        $append = [
            AuthorizationInterface::ROLE_EDITOR => [
                ExternalEmbedContentService::PERMISSION_EMBED_EXTERNAL,
            ],
        ];

        foreach ($append as $roleId => $permissionsToAdd) {
            $record = $this->roles->get($roleId);
            if ($record === null) {
                continue;
            }

            $permissions = $record->permissions;
            $changed = false;
            foreach ($permissionsToAdd as $permission) {
                if (!in_array($permission, $permissions, true)) {
                    $permissions[] = $permission;
                    $changed = true;
                }
            }

            if ($changed) {
                $this->roles->save(
                    $roleId,
                    $record->name,
                    PermissionCatalog::normalizeList($permissions),
                    true,
                );
            }
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
