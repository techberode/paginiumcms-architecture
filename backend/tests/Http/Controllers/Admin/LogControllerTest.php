<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Logging\LogStoragePaths;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Http\TestCase;

final class LogControllerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = LogStoragePaths::app();
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->logFile = $dir . '/' . date('Y-m-d') . '.json';
        file_put_contents($this->logFile, JsonHelper::encode([
            [
                'id' => 'log_api_test',
                'timestamp' => date('Y-m-d H:i:s'),
                'severity' => LogSeverity::INFO,
                'category' => 'test',
                'message' => 'Log controller test entry',
            ],
        ]));
    }

    protected function tearDown(): void
    {
        if (is_file($this->logFile)) {
            @unlink($this->logFile);
        }

        parent::tearDown();
    }

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
        $this->assertArrayHasKey('total', $listData['data']);
    }

    public function testAdminCanBulkArchiveAndDeleteLogs(): void
    {
        $this->loginAsAdminUser();

        $archiveRequest = $this->createJsonRequest('POST', '/api/admin/logs/bulk', [
            'ids' => ['log_api_test'],
            'action' => 'archive',
        ]);
        $archiveResponse = $this->handleRequest($archiveRequest);
        $archiveData = $this->getJsonResponse($archiveResponse);

        $this->assertEquals(200, $archiveResponse->getStatusCode());
        $this->assertTrue($archiveData['success']);
        $this->assertSame(1, $archiveData['data']['succeeded']);

        $deleteRequest = $this->createJsonRequest('POST', '/api/admin/logs/bulk', [
            'ids' => ['log_api_test'],
            'action' => 'delete',
        ]);
        $deleteResponse = $this->handleRequest($deleteRequest);
        $deleteData = $this->getJsonResponse($deleteResponse);

        $this->assertEquals(200, $deleteResponse->getStatusCode());
        $this->assertTrue($deleteData['success']);
        $this->assertSame(1, $deleteData['data']['succeeded']);
    }

    public function testAdminCanDeleteAllLogs(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('POST', '/api/admin/logs/delete-all');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('deleted_files', $data['data']);
    }
}
