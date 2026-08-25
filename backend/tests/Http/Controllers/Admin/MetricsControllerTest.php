<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Performance\PerformanceSampleStore;
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

    public function testApmClearRequiresAuth(): void
    {
        $request = $this->createJsonRequest('POST', '/api/admin/metrics/apm/clear', []);
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testApmClearEmptiesSamplesAndBreaches(): void
    {
        $this->loginAsAdminUser();

        $samples = $this->container()->get(PerformanceSampleStore::class);
        $samples->append([
            'ts' => time(),
            'route' => 'GET /api/test',
            'method' => 'GET',
            'status' => 200,
            'duration_ms' => 12.5,
            'memory_delta_mb' => 0.1,
            'storage_reads' => 1,
            'storage_writes' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ]);

        $this->assertNotSame([], $samples->all());

        $request = $this->createJsonRequest('POST', '/api/admin/metrics/apm/clear', []);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['cleared'] ?? false);
        $this->assertSame([], $samples->all());
    }
}
