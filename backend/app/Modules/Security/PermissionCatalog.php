<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security;

use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;

/**
 * Canonical list of RBAC permissions and role defaults (settings-driven ACL).
 */
final class PermissionCatalog
{
    /** @var list<string> */
    public const ALL = [
        'user:manage',
        'content:manage',
        'content:create',
        'content:edit',
        'content:delete',
        'content:view',
        'media:manage',
        'media:upload',
        'media:delete',
        'settings:manage',
        'logs:view',
        'profile:edit',
    ];

    /** @var list<string> */
    private const CONFIGURABLE_ROLES = [
        AuthorizationInterface::ROLE_ADMIN,
        AuthorizationInterface::ROLE_EDITOR,
        AuthorizationInterface::ROLE_USER,
    ];

    /**
     * @return array<string, list<string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            AuthorizationInterface::ROLE_ADMIN => [
                'user:manage',
                'content:manage',
                'media:manage',
                'settings:manage',
                'logs:view',
            ],
            AuthorizationInterface::ROLE_EDITOR => [
                'content:create',
                'content:edit',
                'content:delete',
                'media:upload',
                'media:delete',
            ],
            AuthorizationInterface::ROLE_USER => [
                'content:view',
                'profile:edit',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function configurableRoles(): array
    {
        return self::CONFIGURABLE_ROLES;
    }

    public static function settingsKeyForRole(string $role): string
    {
        return match (strtoupper($role)) {
            AuthorizationInterface::ROLE_ADMIN => 'permissionsAdmin',
            AuthorizationInterface::ROLE_EDITOR => 'permissionsEditor',
            AuthorizationInterface::ROLE_USER => 'permissionsUser',
            default => throw new \InvalidArgumentException(sprintf('Role "%s" is not configurable', $role)),
        };
    }

    /**
     * @param list<string> $permissions
     */
    public static function encodePermissions(array $permissions): string
    {
        $normalized = self::normalizeList($permissions);

        return implode(',', $normalized);
    }

    /**
     * @return list<string>
     */
    public static function decodePermissions(string $encoded): array
    {
        if (trim($encoded) === '') {
            return [];
        }

        $parts = array_map(static fn (string $part): string => trim($part), explode(',', $encoded));

        return self::normalizeList($parts);
    }

    /**
     * @param list<string> $permissions
     * @return list<string>
     */
    public static function normalizeList(array $permissions): array
    {
        $valid = [];
        foreach ($permissions as $permission) {
            if ($permission === '') {
                continue;
            }
            if (!in_array($permission, self::ALL, true)) {
                continue;
            }
            $valid[] = $permission;
        }

        return array_values(array_unique($valid));
    }

    /**
     * @return array<string, bool|string>
     */
    public static function defaultAccessControlSettings(): array
    {
        $defaults = [
            'pathAclEnabled' => false,
            'pathAclRulesJson' => '[]',
        ];

        foreach (self::defaultRolePermissions() as $role => $permissions) {
            $defaults[self::settingsKeyForRole($role)] = self::encodePermissions($permissions);
        }

        return $defaults;
    }
}
