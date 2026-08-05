<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class MetricsControllerTest extends TestCase
{
    public function testApmSummaryRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/metrics/apm');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testApmSummaryReturnsConfigAndSummary(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('GET', '/api/admin/metrics/apm');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('config', $data['data']);
        $this->assertArrayHasKey('summary', $data['data']);
        $this->assertArrayHasKey('recent_breaches', $data['data']);
        $this->assertFalse($data['data']['config']['enabled']);
        $this->assertSame('suggest', $data['data']['config']['remediation_mode']);
    }
}
