<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\PermissionCatalog;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PHPUnit\Framework\TestCase;

final class AuthorizationManagerSettingsReloadTest extends TestCase
{
    public function testReloadFromSettingsOverridesEditorPermissions(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('accessControl')->willReturn([
            'permissionsEditor' => 'content:view',
        ]);

        $authz = new AuthorizationManager(null, $settings);

        $user = new User();
        $user->setRoles(['EDITOR']);

        $this->assertTrue($authz->hasPermission($user, 'content:view'));
        $this->assertFalse($authz->hasPermission($user, 'content:edit'));
    }

    public function testDefaultPermissionsUsedWhenSettingsEmpty(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('accessControl')->willReturn([]);

        $authz = new AuthorizationManager(null, $settings);
        $defaults = PermissionCatalog::defaultRolePermissions()['EDITOR'];

        $user = new User();
        $user->setRoles(['EDITOR']);

        foreach ($defaults as $permission) {
            $this->assertTrue($authz->hasPermission($user, $permission));
        }
    }

    public function testReloadEnsuresContentManageForAdminWithoutContentPermissions(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('accessControl')->willReturn([
            'permissionsAdmin' => 'user:manage,settings:manage,media:manage',
        ]);

        $authz = new AuthorizationManager(null, $settings);

        $user = new User();
        $user->setRoles(['ADMIN']);

        $this->assertTrue($authz->hasPermission($user, 'content:edit'));
        $this->assertTrue($authz->hasPermission($user, 'content:create'));
    }

    public function testReloadEnsuresContentEditWhenEditorCanCreateOnly(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('accessControl')->willReturn([
            'permissionsEditor' => 'content:create,media:upload',
        ]);

        $authz = new AuthorizationManager(null, $settings);

        $user = new User();
        $user->setRoles(['EDITOR']);

        $this->assertTrue($authz->hasPermission($user, 'content:create'));
        $this->assertTrue($authz->hasPermission($user, 'content:edit'));
    }
}
