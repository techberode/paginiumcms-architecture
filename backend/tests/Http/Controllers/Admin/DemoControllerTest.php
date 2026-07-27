<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

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

    public function testDemoQuickLoginForbiddenWhenDemoDisabled(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/demo/quick-login')
        );

        $this->assertSame(404, $response->getStatusCode());
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
