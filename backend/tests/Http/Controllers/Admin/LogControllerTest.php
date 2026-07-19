<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class LogControllerTest extends TestCase
{
    public function testStatsRequiresAdmin(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/logs/stats');
        $response = $this->handleRequest($request);
        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    public function testAdminCanListLogsAndStats(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $statsRequest = $this->createJsonRequest('GET', '/api/admin/logs/stats');
        $statsResponse = $this->handleRequest($statsRequest);
        $statsData = $this->getJsonResponse($statsResponse);

        $this->assertEquals(200, $statsResponse->getStatusCode());
        $this->assertTrue($statsData['success']);
        $this->assertArrayHasKey('by_severity', $statsData['data']);

        $listRequest = $this->createJsonRequest('GET', '/api/admin/logs?limit=10');
        $listResponse = $this->handleRequest($listRequest);
        $listData = $this->getJsonResponse($listResponse);

        $this->assertEquals(200, $listResponse->getStatusCode());
        $this->assertTrue($listData['success']);
        $this->assertArrayHasKey('items', $listData['data']);
    }
}
