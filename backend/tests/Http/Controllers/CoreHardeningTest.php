<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Http\TestCase;

class CoreHardeningTest extends TestCase
{
    /**
     * @param array<int|string, mixed> $patch
     */
    private function patchGeneralSettings(array $patch): void
    {
        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('general', array_merge($settings->group('general'), $patch));
    }

    /**
     * @param array<int|string, mixed> $patch
     */
    private function patchMaintenanceSettings(array $patch): void
    {
        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('maintenance', array_merge($settings->group('maintenance'), $patch));
    }

    public function testUserRoleCannotCreatePage(): void
    {
        $userData = $this->createTestUser();
        $this->assertEquals(201, $userData['response']->getStatusCode());
        $this->loginTestUser($userData['email'], $userData['password']);

        $request = $this->createJsonRequest('POST', '/api/pages', [
            'title' => 'Forbidden',
            'slug' => 'forbidden-' . uniqid(),
            'content' => '# Test',
            'status' => 'draft',
        ]);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testEditorRoleCanCreatePage(): void
    {
        $userData = $this->createTestUser();
        $repo = $this->app->getContainer()->get(UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        $this->assertNotNull($user);
        $user->setRoles(['EDITOR']);
        $repo->save($user);

        $this->loginTestUser($userData['email'], $userData['password']);
        if ($this->currentUser instanceof User) {
            $this->currentUser->setRoles(['EDITOR']);
        }

        $slug = 'editor-page-' . uniqid();
        $request = $this->createJsonRequest('POST', '/api/pages', [
            'title' => 'Editor page',
            'slug' => $slug,
            'content' => '# Editor',
            'status' => 'draft',
        ]);

        $response = $this->handleRequest($request);

        $this->assertNotEquals(403, $response->getStatusCode(), 'Editor must not receive forbidden');
    }

    public function testMaintenanceModeBlocksPublicApi(): void
    {
        $this->patchMaintenanceSettings(['mode' => 'under_maintenance']);

        $request = $this->createJsonRequest('GET', '/api/pages');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(503, $response->getStatusCode());
        $this->assertTrue($data['maintenance'] ?? false);

        $this->patchMaintenanceSettings(['mode' => 'off']);
    }

    public function testMaintenanceModeAllowsHealthCheck(): void
    {
        $this->patchMaintenanceSettings(['mode' => 'under_maintenance']);

        $request = $this->createJsonRequest('GET', '/api/health');
        $response = $this->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode());

        $this->patchMaintenanceSettings(['mode' => 'off']);
    }

    public function testRegistrationDisabledBySetting(): void
    {
        $this->patchGeneralSettings(['allowRegistration' => false]);

        try {
            $password = 'StrongP@ssw0rd123!';
            $request = $this->createJsonRequest('POST', '/api/auth/register', [
                'email' => 'blocked_' . uniqid() . '@example.com',
                'password' => $password,
                'passwordConfirm' => $password,
                'name' => 'Blocked User',
            ]);

            $response = $this->handleRequest($request);
            $data = $this->getJsonResponse($response);

            $this->assertEquals(403, $response->getStatusCode());
            $this->assertFalse($data['success']);
            $this->assertStringContainsString('vypnutá', (string) ($data['error'] ?? ''));
        } finally {
            $this->patchGeneralSettings(['allowRegistration' => true]);
        }
    }

    public function testRegistrationDisabledDuringMaintenance(): void
    {
        $this->patchMaintenanceSettings(['mode' => 'under_maintenance']);

        try {
            $password = 'StrongP@ssw0rd123!';
            $request = $this->createJsonRequest('POST', '/api/auth/register', [
                'email' => 'blocked_maint_' . uniqid() . '@example.com',
                'password' => $password,
                'passwordConfirm' => $password,
                'name' => 'Blocked User',
            ]);

            $response = $this->handleRequest($request);
            $data = $this->getJsonResponse($response);

            $this->assertEquals(403, $response->getStatusCode());
            $this->assertFalse($data['success']);
            $this->assertStringContainsString('údržby', (string) ($data['error'] ?? ''));
        } finally {
            $this->patchMaintenanceSettings(['mode' => 'off']);
        }
    }

    public function testStorageRouteServesFile(): void
    {
        $storageRoot = realpath(__DIR__ . '/../../../storage');
        $this->assertNotFalse($storageRoot);

        $relative = 'app/content/media/hardening-test.txt';
        $fullPath = $storageRoot . '/' . $relative;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, 'storage-ok');

        $request = $this->createJsonRequest('GET', '/storage/' . $relative);
        $response = $this->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('storage-ok', (string) $response->getBody());

        @unlink($fullPath);
    }

    public function testStorageRouteRejectsPathTraversal(): void
    {
        $request = $this->createJsonRequest('GET', '/storage/app/content/../../../etc/passwd');
        $response = $this->handleRequest($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUserRoleCannotListMedia(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $request = $this->createJsonRequest('GET', '/api/media');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testMaintenanceAllowsAuthenticatedEditor(): void
    {
        $userData = $this->createTestUser();
        $repo = $this->app->getContainer()->get(UserRepository::class);
        $user = $repo->findByEmail($userData['email']);
        $this->assertNotNull($user);
        $user->setRoles(['EDITOR']);
        $repo->save($user);

        $this->patchMaintenanceSettings(['mode' => 'under_maintenance']);

        $this->loginTestUser($userData['email'], $userData['password']);
        if ($this->currentUser instanceof User) {
            $this->currentUser->setRoles(['EDITOR']);
        }

        $request = $this->createJsonRequest('GET', '/api/pages');
        $response = $this->handleRequest($request);

        $this->assertNotEquals(503, $response->getStatusCode());

        $this->patchMaintenanceSettings(['mode' => 'off']);
    }
}
