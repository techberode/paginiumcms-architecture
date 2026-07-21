<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class TranslationControllerTest extends TestCase
{
    public function testCatalogRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/translations/catalog');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCatalogReturnsFilesWithoutDeveloperUnlock(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('GET', '/api/admin/translations/catalog');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']['files']);
        $this->assertNotSame([], $data['data']['files']);
    }

    public function testGetFileReturnsBackendLangContent(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest(
            'GET',
            '/api/admin/translations/file?path=backend/lang/sk/content.php'
        );
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertStringContainsString("'not_found'", (string) $data['data']['content']);
        $this->assertSame('php', $data['data']['language']);
    }

    public function testSaveRejectsInvalidPath(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('POST', '/api/admin/translations/save', [
            'path' => 'backend/app/Core/Bootstrap.php',
            'content' => '<?php echo "nope";',
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }
}
