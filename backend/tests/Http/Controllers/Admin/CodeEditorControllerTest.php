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

    private function unlockDeveloperGate(): void
    {
        putenv('APP_DEBUG=true');
        $_ENV['APP_DEBUG'] = 'true';
        $_SESSION['paginium_dev_unlocked_until'] = time() + 3600;
        $_SESSION['paginium_dev_unlock_method'] = 'test';
    }
}
