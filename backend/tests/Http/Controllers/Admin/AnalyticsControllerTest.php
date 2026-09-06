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

    public function testBanBotRequiresAuth(): void
    {
        $request = $this->createJsonRequest('POST', '/api/admin/analytics/bots/ban', [
            'ip' => '203.0.113.50',
            'bot_name' => 'curl',
        ]);
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testBanBotRejectsInvalidIp(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('POST', '/api/admin/analytics/bots/ban', [
            'ip' => 'not-an-ip',
            'bot_name' => 'curl',
        ]);
        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testBanBotAddsTemporaryJail(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('POST', '/api/admin/analytics/bots/ban', [
            'ip' => '203.0.113.50',
            'bot_name' => 'curl/8.4.0',
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('ban', $data['data']);
    }

    public function testOverviewIncludesTrendsAndPlatforms(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('GET', '/api/admin/analytics/overview?period=7');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('trends', $data['data']['overview']);
        $this->assertArrayHasKey('platforms', $data['data']);
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
