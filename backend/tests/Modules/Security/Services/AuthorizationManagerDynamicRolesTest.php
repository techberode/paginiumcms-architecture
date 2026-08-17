<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\RoleRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class AuthorizationManagerDynamicRolesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        vfsStream::setup('storage');
        $this->root = vfsStream::url('storage');
    }

    public function testCustomRolePermissionsApplyAfterReload(): void
    {
        $repository = $this->repository();
        $repository->save('MODERATOR', 'Moderator', ['content:view']);

        $authz = new AuthorizationManager(null, null, $repository);

        $user = new User();
        $user->setRoles(['MODERATOR']);

        $this->assertTrue($authz->hasPermission($user, 'content:view'));
        $this->assertFalse($authz->hasPermission($user, 'content:edit'));
    }

    public function testCustomRoleOverridesDefaultForSameId(): void
    {
        $repository = $this->repository();
        $repository->save('EDITOR', 'Editor', ['content:view'], true);

        $authz = new AuthorizationManager(null, null, $repository);

        $user = new User();
        $user->setRoles(['EDITOR']);

        $this->assertTrue($authz->hasPermission($user, 'content:view'));
        $this->assertFalse($authz->hasPermission($user, 'content:edit'));
    }

    public function testSuperAdminStillBypassesPermissionChecks(): void
    {
        $authz = new AuthorizationManager(null, null, $this->repository());

        $user = new User();
        $user->setRoles(['SUPER_ADMIN']);

        $this->assertTrue($authz->hasPermission($user, 'settings:manage'));
    }

    private function repository(): RoleRepository
    {
        $validator = new FileValidator($this->root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        return new RoleRepository($reader, $writer);
    }
}
