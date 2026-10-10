<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Tests\Http\TestCase;

final class MetricsControllerTest extends TestCase
{
    public function testLoadHintRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/metrics/load-hint');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testLoadHintReturnsLevel(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('GET', '/api/admin/metrics/load-hint');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('normal', $data['data']['level']);
        $this->assertIsArray($data['data']['reasons']);
    }

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
        $this->assertArrayHasKey('load_hint', $data['data']);
        $this->assertArrayHasKey('host_metrics', $data['data']);
        $this->assertSame('normal', $data['data']['load_hint']['level']);
        $this->assertFalse($data['data']['config']['enabled']);
        $this->assertSame('suggest', $data['data']['config']['remediation_mode']);
    }

    public function testApmClearRequiresAuth(): void
    {
        $request = $this->createJsonRequest('POST', '/api/admin/metrics/apm/clear', []);
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testHostMetricsRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/metrics/host');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testHostIngestRejectsMissingToken(): void
    {
        $request = $this->createJsonRequest('POST', '/api/admin/metrics/host/ingest', ['collected_at' => gmdate('c')]);
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
