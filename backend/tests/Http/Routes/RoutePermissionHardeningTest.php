<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Routes;

use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Http\TestCase;

/**
 * ISS-204: Role-only admin route groups now also require PermissionMiddleware;
 * full settings reset is SUPER_ADMIN-only.
 */
final class RoutePermissionHardeningTest extends TestCase
{
    public function testEditorCannotResetAllSettings(): void
    {
        $this->loginWithRole('EDITOR');

        $response = $this->handleRequest($this->createJsonRequest('DELETE', '/api/admin/settings'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminCannotResetAllSettings(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('DELETE', '/api/admin/settings'));

        $this->assertSame(403, $response->getStatusCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adminReadableRoutes(): iterable
    {
        yield 'backups' => ['/api/admin/backups'];
        yield 'logs' => ['/api/admin/logs'];
        yield 'extensions' => ['/api/admin/extensions'];
        yield 'users' => ['/api/admin/users'];
    }

    /**
     * @dataProvider adminReadableRoutes
     */
    public function testAdminKeepsAccessAfterPermissionHardening(string $path): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('GET', $path));

        $this->assertNotSame(403, $response->getStatusCode(), $path);
        $this->assertNotSame(401, $response->getStatusCode(), $path);
    }

    public function testEditorCannotReachBackups(): void
    {
        $this->loginWithRole('EDITOR');

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/backups'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminCannotImportBackupArchive(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/backups/import')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminCannotImportExtensionZip(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/extensions/import')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    private function loginWithRole(string $role): void
    {
        $userData = $this->createTestUser();
        $repo = $this->container()->get(UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        $this->assertNotNull($user);
        $user->setRoles([$role]);
        $repo->save($user);

        $this->loginTestUser($userData['email'], $userData['password']);
    }
}
