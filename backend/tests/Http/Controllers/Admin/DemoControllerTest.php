<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class DemoControllerTest extends TestCase
{
    public function testDemoStatusRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/demo/status')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testDemoStatusForAdmin(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/demo/status')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('enabled', $data['data']);
        $this->assertArrayHasKey('storage_path', $data['data']);
        $this->assertArrayHasKey('next_reset_at', $data['data']);
    }

    public function testDemoPublicInfoWhenDemoDisabled(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/demo/public-info')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertFalse($data['data']['enabled']);
        $this->assertArrayNotHasKey('credentials', $data['data']);
    }

    public function testDemoPublicInfoIncludesCredentialsWhenDemoEnabled(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $this->app = require __DIR__ . '/../../../../bootstrap/app.php';

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/demo/public-info')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['data']['enabled']);
        $this->assertSame('demo@paginiumcms.com', $data['data']['credentials']['email'] ?? null);
        $this->assertSame('Demo123!', $data['data']['credentials']['password'] ?? null);
    }

    public function testDemoQuickLoginForbiddenWhenDemoDisabled(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/demo/quick-login')
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDemoPublicInfoFailsClosedWhenMisconfiguredOnProduction(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('firewall', array_merge($settings->group('firewall'), [
            'enabled' => false,
        ]));

        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/demo/public-info')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertFalse($data['data']['enabled']);
    }

    public function testDemoResetForbiddenWhenDemoDisabled(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/demo/reset')
        );

        $this->assertSame(400, $response->getStatusCode());
    }
}
