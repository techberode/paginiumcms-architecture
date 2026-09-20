<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class PlaygroundControllerTest extends TestCase
{
    public function testShowRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/playground'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testShowRequiresSuperAdmin(): void
    {
        $user = $this->createTestUser();
        $this->loginTestUser($user['email'], $user['password']);

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/playground'));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testShowReturnsDisabledCatalogForSuperAdmin(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/playground'));
        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertFalse($payload['data']['enabled']);
        $this->assertContains('react-ts', $payload['data']['templates']);
        $ids = array_map(static fn (array $pack): string => (string) $pack['packId'], $payload['data']['packs']);
        $this->assertContains('paginium-starter', $ids);
    }

    public function testAssetRequiresEnabledPlayground(): void
    {
        $this->loginAsSuperAdminUser();
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/playground/assets/paginium-starter/StatCard.tsx')
        );
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAssetServesBundledFileWhenEnabled(): void
    {
        $this->loginAsSuperAdminUser();
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('playground', array_merge($settings->group('playground'), [
            'enabled' => true,
            'enabledPacks' => 'paginium-starter',
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/playground/assets/paginium-starter/StatCard.tsx')
        );
        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertStringContainsString('StatCard', (string) ($payload['data']['content'] ?? ''));
    }

    public function testAssetRejectsTraversal(): void
    {
        $this->loginAsSuperAdminUser();
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('playground', array_merge($settings->group('playground'), [
            'enabled' => true,
            'enabledPacks' => 'paginium-starter',
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/playground/assets/paginium-starter/../PlaygroundSettings.php')
        );
        $this->assertContains($response->getStatusCode(), [400, 404]);
    }
}
