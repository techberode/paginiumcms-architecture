<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class CodeEditorControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->unlockDeveloperGate();
    }

    public function testListFilesRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/code-editor/files');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testListFilesRequiresDeveloperUnlock(): void
    {
        $this->loginAsAdminUser();
        unset($_SESSION['paginium_dev_unlocked_until'], $_SESSION['paginium_dev_unlock_method']);

        $request = $this->createJsonRequest('GET', '/api/admin/code-editor/files');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function testListFilesReturnsFileInfoArray(): void
    {
        $this->loginAsAdminUser();
        $this->unlockDeveloperGate();

        $request = $this->createJsonRequest(
            'GET',
            '/api/admin/code-editor/files?directory=backend/app/Modules'
        );
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        if ($data['data'] !== []) {
            $this->assertArrayHasKey('path', $data['data'][0]);
            $this->assertArrayHasKey('name', $data['data'][0]);
        }
    }

    public function testListDirectoriesReturnsAllowedRoots(): void
    {
        $this->loginAsAdminUser();
        $this->unlockDeveloperGate();

        $request = $this->createJsonRequest('GET', '/api/admin/code-editor/directories');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertContains('backend/app/Modules', $data['data']['directories']);
        $this->assertContains('backend/config', $data['data']['directories']);
    }

    public function testListAllFilesUsesAllowedRootsOnly(): void
    {
        $this->loginAsAdminUser();
        $this->unlockDeveloperGate();

        $request = $this->createJsonRequest('GET', '/api/admin/code-editor/files?directory=all');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('all', $data['directory']);
        $this->assertIsArray($data['directories']);
        foreach ($data['data'] as $file) {
            $path = (string) $file['path'];
            $allowed = false;
            foreach ($data['directories'] as $root) {
                if (str_starts_with($path, (string) $root)) {
                    $allowed = true;
                    break;
                }
            }
            $this->assertTrue($allowed, 'Unexpected path outside allowed roots: ' . $path);
        }
    }

    public function testSaveFileRejectsPolicyViolation(): void
    {
        $this->loginAsAdminUser();
        $this->unlockDeveloperGate();

        $request = $this->createJsonRequest('POST', '/api/admin/code-editor/save', [
            'path' => 'backend/app/Modules/PolicyTest.php',
            'content' => '<?php eval("bad");',
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertNotEmpty($data['errors']['security'] ?? []);
    }

    public function testCreateDeleteAndRestoreFileFlow(): void
    {
        $this->loginAsAdminUser();
        $this->unlockDeveloperGate();

        $path = 'backend/app/Modules/CodeEditorFlowTest_' . uniqid() . '.php';

        $createRequest = $this->createJsonRequest('POST', '/api/admin/code-editor/file', [
            'path' => $path,
            'content' => '<?php echo "flow-v1";',
        ]);
        $createResponse = $this->handleRequest($createRequest);
        $this->assertSame(201, $createResponse->getStatusCode());

        $saveRequest = $this->createJsonRequest('POST', '/api/admin/code-editor/save', [
            'path' => $path,
            'content' => '<?php echo "flow-v2";',
        ]);
        $this->assertSame(200, $this->handleRequest($saveRequest)->getStatusCode());

        $backupsRequest = $this->createJsonRequest('GET', '/api/admin/code-editor/backups?path=' . rawurlencode($path));
        $backupsData = $this->getJsonResponse($this->handleRequest($backupsRequest));
        $this->assertTrue($backupsData['success']);
        $this->assertNotEmpty($backupsData['data']);

        $restoreRequest = $this->createJsonRequest('POST', '/api/admin/code-editor/restore', [
            'path' => $path,
            'backup_file' => $backupsData['data'][0],
        ]);
        $restoreResponse = $this->handleRequest($restoreRequest);
        $this->assertSame(200, $restoreResponse->getStatusCode());

        $deleteRequest = $this->createJsonRequest('DELETE', '/api/admin/code-editor/file?path=' . rawurlencode($path));
        $this->assertSame(200, $this->handleRequest($deleteRequest)->getStatusCode());
    }

    private function unlockDeveloperGate(): void
    {
        putenv('APP_DEBUG=true');
        $_ENV['APP_DEBUG'] = 'true';
        $_SESSION['paginium_dev_unlocked_until'] = time() + 3600;
        $_SESSION['paginium_dev_unlock_method'] = 'test';
    }
}
