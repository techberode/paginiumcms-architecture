<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

class GitHubControllerTest extends TestCase
{
    public function testStatusRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/github/status');
        $response = $this->handleRequest($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testStatusAsAdmin(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('GET', '/api/admin/github/status');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('configured', $data['data']);
    }
}
