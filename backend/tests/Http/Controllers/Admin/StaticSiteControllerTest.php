<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class StaticSiteControllerTest extends TestCase
{
    public function testStatusRejectsPlainUser(): void
    {
        $registered = $this->createTestUser('static-user-' . uniqid('', true) . '@example.com');
        $this->loginTestUser($registered['email'], $registered['password']);

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/static/status'));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testStatusAllowsSuperAdmin(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/static/status'));
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->getJsonResponse($response);
        $this->assertTrue((bool) ($json['success'] ?? false));
        $this->assertArrayHasKey('renderMode', $json['data'] ?? []);
        $this->assertSame('static', $json['data']['tree'] ?? null);
        $this->assertFalse((bool) ($json['data']['publicServe'] ?? true));
        $this->assertSame('/static-html', $json['data']['publicPrefix'] ?? null);
    }
}
