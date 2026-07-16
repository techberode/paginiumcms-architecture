<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PHPUnit\Framework\TestCase;

class AuthorizationManagerManagePermissionTest extends TestCase
{
    public function testContentManageCoversCreateEditDelete(): void
    {
        $authz = new AuthorizationManager();
        $user = new User();
        $user->setEmail('admin@test.local');
        $user->setRoles(['ADMIN']);

        $this->assertTrue($authz->hasPermission($user, 'content:create'));
        $this->assertTrue($authz->hasPermission($user, 'content:edit'));
        $this->assertTrue($authz->hasPermission($user, 'media:upload'));
    }
}
