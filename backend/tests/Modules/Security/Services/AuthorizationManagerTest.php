<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PHPUnit\Framework\TestCase;

class AuthorizationManagerTest extends TestCase
{
    private AuthorizationManager $authz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authz = new AuthorizationManager();
    }

    public function testHasRole(): void
    {
        $user = new User();
        $user->setRoles(['ADMIN', 'EDITOR']);

        $this->assertTrue($this->authz->hasRole($user, 'ADMIN'));
        $this->assertTrue($this->authz->hasRole($user, 'EDITOR'));
        $this->assertFalse($this->authz->hasRole($user, 'SUPER_ADMIN'));
        $this->assertFalse($this->authz->hasRole($user, 'USER'));
    }

    public function testHasRoleWithArray(): void
    {
        $user = new User();
        $user->setRoles(['EDITOR']);

        $this->assertTrue($this->authz->hasRole($user, ['ADMIN', 'EDITOR']));
        $this->assertFalse($this->authz->hasRole($user, ['ADMIN', 'SUPER_ADMIN']));
    }

    public function testSuperAdminHasAllPermissions(): void
    {
        $user = new User();
        $user->setRoles(['SUPER_ADMIN']);

        $this->assertTrue($this->authz->hasPermission($user, 'anything'));
        $this->assertTrue($this->authz->hasPermission($user, 'user:manage'));
        $this->assertTrue($this->authz->hasPermission($user, 'content:delete'));
    }

    public function testAdminPermissions(): void
    {
        $user = new User();
        $user->setRoles(['ADMIN']);

        $this->assertTrue($this->authz->hasPermission($user, 'user:manage'));
        $this->assertTrue($this->authz->hasPermission($user, 'content:manage'));
        $this->assertTrue($this->authz->hasPermission($user, 'settings:manage'));
        $this->assertFalse($this->authz->hasPermission($user, 'content:create')); // Admin nemá create
    }

    public function testEditorPermissions(): void
    {
        $user = new User();
        $user->setRoles(['EDITOR']);

        $this->assertTrue($this->authz->hasPermission($user, 'content:create'));
        $this->assertTrue($this->authz->hasPermission($user, 'content:edit'));
        $this->assertTrue($this->authz->hasPermission($user, 'content:delete'));
        $this->assertTrue($this->authz->hasPermission($user, 'media:upload'));
        $this->assertFalse($this->authz->hasPermission($user, 'user:manage'));
        $this->assertFalse($this->authz->hasPermission($user, 'settings:manage'));
    }

    public function testUserPermissions(): void
    {
        $user = new User();
        $user->setRoles(['USER']);

        $this->assertTrue($this->authz->hasPermission($user, 'content:view'));
        $this->assertTrue($this->authz->hasPermission($user, 'profile:edit'));
        $this->assertFalse($this->authz->hasPermission($user, 'content:create'));
        $this->assertFalse($this->authz->hasPermission($user, 'content:edit'));
    }

    public function testAddAndRemoveRole(): void
    {
        $user = new User();
        $user->setRoles(['USER']);

        $this->authz->addRole($user, 'EDITOR');
        $this->assertTrue($this->authz->hasRole($user, 'EDITOR'));

        $this->authz->removeRole($user, 'EDITOR');
        $this->assertFalse($this->authz->hasRole($user, 'EDITOR'));
    }

    public function testAddRoleThrowsException(): void
    {
        $user = new User();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Rola "NON_EXISTENT" neexistuje');

        $this->authz->addRole($user, 'NON_EXISTENT');
    }

    public function testRequireRolePasses(): void
    {
        $user = new User();
        $user->setRoles(['ADMIN']);

        $this->authz->requireRole($user, 'ADMIN');
        $this->addToAssertionCount(1);
    }

    public function testRequireRoleThrowsException(): void
    {
        $user = new User();
        $user->setRoles(['USER']);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Vyžaduje sa rola: ADMIN');

        $this->authz->requireRole($user, 'ADMIN');
    }

    public function testRequirePermissionPasses(): void
    {
        $user = new User();
        $user->setRoles(['ADMIN']);

        $this->authz->requirePermission($user, 'user:manage');
        $this->addToAssertionCount(1);
    }

    public function testRequirePermissionThrowsException(): void
    {
        $user = new User();
        $user->setRoles(['USER']);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Nedostatočné oprávnenie: user:manage');

        $this->authz->requirePermission($user, 'user:manage');
    }

    public function testSetRolePermissions(): void
    {
        $this->authz->setRolePermissions('CUSTOM_ROLE', ['custom:permission']);

        $permissions = $this->authz->getRolePermissions('CUSTOM_ROLE');
        $this->assertEquals(['custom:permission'], $permissions);
    }

    public function testGetRoles(): void
    {
        $user = new User();
        $user->setRoles(['ADMIN', 'EDITOR']);

        $this->assertEquals(['ADMIN', 'EDITOR'], $this->authz->getRoles($user));
    }
}
