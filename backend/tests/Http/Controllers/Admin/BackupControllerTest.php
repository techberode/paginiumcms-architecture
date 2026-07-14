<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

class BackupControllerTest extends TestCase
{
    public function testListBackups(): void
    {
        $userData = $this->loginAsAdminUser();
        $this->assertEquals(200, $userData['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/admin/backups');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('backups', $data);
        $this->assertIsArray($data['backups']);
    }

    public function testCreateBackup(): void
    {
        $userData = $this->loginAsAdminUser();
        $this->assertEquals(200, $userData['response']->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/admin/backups', [
            'name' => 'Test Backup API',
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('backup', $data);
        $this->assertEquals('Test Backup API', $data['backup']['name']);
    }

    public function testCreateBackupWithoutName(): void
    {
        $userData = $this->loginAsAdminUser();
        $this->assertEquals(200, $userData['response']->getStatusCode());

        $request = $this->createJsonRequest('POST', '/api/admin/backups', []);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testDeleteBackup(): void
    {
        $userData = $this->loginAsAdminUser();
        $this->assertEquals(200, $userData['response']->getStatusCode());

        $createRequest = $this->createJsonRequest('POST', '/api/admin/backups', [
            'name' => 'Test Backup Delete',
        ]);
        $createResponse = $this->handleRequest($createRequest);
        $createData = $this->getJsonResponse($createResponse);
        $backupId = $createData['backup']['id'] ?? null;

        $this->assertNotNull($backupId);

        $deleteRequest = $this->createJsonRequest('DELETE', "/api/admin/backups/{$backupId}");
        $deleteResponse = $this->handleRequest($deleteRequest);
        $deleteData = $this->getJsonResponse($deleteResponse);

        $this->assertEquals(200, $deleteResponse->getStatusCode());
        $this->assertTrue($deleteData['success']);
    }
}
