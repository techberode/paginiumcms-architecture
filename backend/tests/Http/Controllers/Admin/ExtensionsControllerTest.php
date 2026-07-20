<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class ExtensionsControllerTest extends TestCase
{
    public function testExtensionsListRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/extensions')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testExtensionsListForAdmin(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/extensions')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('extensions', $data['data']);
        $this->assertIsArray($data['data']['extensions']);
    }

    public function testImportRequiresZipFile(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/extensions/import')
        );

        $this->assertSame(400, $response->getStatusCode());
    }
}
