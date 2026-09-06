<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Setup;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Http\TestCase;
use PaginiumCMS\Tests\Support\TestStorageCleaner;

final class SetupControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        TestStorageCleaner::purgeTestUsers();
        parent::tearDown();
    }

    public function testStatusWhenFreshInstall(): void
    {
        $this->prepareFreshInstall();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/setup/status')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['needsSetup']);
        $this->assertFalse($data['data']['installed']);
        $this->assertFalse($data['data']['hasUsers']);
        $this->assertUserIndexIsEmpty();
    }

    public function testStatusWhenInstalledFlagSetButNoUsersNeedsSetup(): void
    {
        if (!TestStorageCleaner::purgeAllUsersForTesting()) {
            $this->markTestSkipped(
                'Shared dev storage contains non-test users; refusing destructive purge (protect local admin accounts).'
            );
        }

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('general', array_merge($settings->group('general'), [
            'installed' => true,
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/setup/status')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['data']['needsSetup']);
        $this->assertTrue($data['data']['installed']);
        $this->assertFalse($data['data']['hasUsers']);
    }

    public function testCompleteCreatesAdminAndMarksInstalled(): void
    {
        $this->prepareFreshInstall();

        $email = 'setup_' . uniqid() . '@example.com';
        $password = 'StrongP@ssw0rd123!';

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/setup/complete', [
                'email' => $email,
                'password' => $password,
                'passwordConfirm' => $password,
                'name' => 'Setup Admin',
                'siteName' => 'My Paginium Site',
                'language' => 'sk',
                'backendPort' => '8099',
                'storageDriver' => 'local',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['installed']);
        $this->assertTrue($data['loginRequired']);
        $this->assertSame('/login', $data['redirectTo']);
        $this->assertArrayNotHasKey('user', $data);

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $this->assertTrue($settings->get('general.installed'));
        $this->assertSame('My Paginium Site', $settings->get('general.siteName'));
        $this->assertSame('sk', $settings->get('general.language'));
        $this->assertFalse($settings->get('general.allowRegistration'));
        $this->assertSame('8099', $settings->get('systemUpdate.backendPort'));
        $this->assertSame('local', $settings->get('media.storageDriver'));

        $users = $this->container()->get(UserRepository::class)->findAll();
        $this->assertCount(1, $users);

        $meResponse = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/auth/me')
        );
        $this->assertSame(401, $meResponse->getStatusCode());
    }

    public function testPreflightReturnsChecks(): void
    {
        $this->prepareFreshInstall();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/setup/preflight')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('ready', $data['data']);
        $this->assertArrayHasKey('checks', $data['data']);
        $this->assertIsArray($data['data']['checks']);
        $this->assertNotSame([], $data['data']['checks']);
    }

    public function testCompleteRejectedWhenAlreadyInstalled(): void
    {
        $this->createTestUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/setup/complete', [
                'email' => 'blocked_' . uniqid() . '@example.com',
                'password' => 'StrongP@ssw0rd123!',
                'passwordConfirm' => 'StrongP@ssw0rd123!',
                'name' => 'Blocked Admin',
                'siteName' => 'Blocked Site',
                'language' => 'en',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testCompleteRejectsMismatchedPasswords(): void
    {
        $this->prepareFreshInstall();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/setup/complete', [
                'email' => 'mismatch_' . uniqid() . '@example.com',
                'password' => 'StrongP@ssw0rd123!',
                'passwordConfirm' => 'StrongP@ssw0rd123?',
                'name' => 'Mismatch Admin',
                'siteName' => 'Mismatch Site',
                'language' => 'en',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    private function prepareFreshInstall(): void
    {
        if (!TestStorageCleaner::purgeAllUsersForTesting()) {
            $this->markTestSkipped(
                'Shared dev storage contains non-test users; refusing destructive purge (protect local admin accounts).'
            );
        }

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('general', array_merge($settings->group('general'), [
            'installed' => false,
            'allowRegistration' => true,
        ]));
    }

    private function assertUserIndexIsEmpty(): void
    {
        $indexPath = TestStorageCleaner::contentRoot() . '/data/index/users.json';
        $this->assertFileExists($indexPath);
        $index = json_decode((string) file_get_contents($indexPath), true);
        $this->assertIsArray($index);
        $this->assertSame([], $index['by_id'] ?? null);
        $this->assertSame([], $index['by_email'] ?? null);
        $this->assertSame([], $index['by_username'] ?? null);
    }
}
