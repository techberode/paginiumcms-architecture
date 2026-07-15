<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class AnalyticsControllerTest extends TestCase
{
    public function testRealtimeRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/analytics/realtime');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRealtimeReturnsSnapshot(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('GET', '/api/admin/analytics/realtime');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('active_visitors', $data['data']);
        $this->assertArrayHasKey('top_active_pages', $data['data']);
        $this->assertArrayHasKey('window_seconds', $data['data']);
    }
}
