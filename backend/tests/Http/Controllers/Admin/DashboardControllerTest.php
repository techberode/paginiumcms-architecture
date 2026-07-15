<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class DashboardControllerTest extends TestCase
{
    public function testDashboardOverviewRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/dashboard/overview');
        $response = $this->handleRequest($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testDashboardOverviewReturnsMonitoringPayload(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('GET', '/api/admin/dashboard/overview');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('locks', $data['data']);
        $this->assertArrayHasKey('conflicts', $data['data']);
        $this->assertArrayHasKey('health', $data['data']);
        $this->assertArrayHasKey('analytics', $data['data']);
        $this->assertArrayHasKey('overview', $data['data']['analytics']);
        $this->assertArrayHasKey('chart', $data['data']['analytics']);
        $this->assertArrayHasKey('realtime', $data['data']['analytics']);
    }
}
