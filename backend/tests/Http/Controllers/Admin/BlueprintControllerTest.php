<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class BlueprintControllerTest extends TestCase
{
    public function testBlueprintListRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/blueprints')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testBlueprintListForAdmin(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/blueprints')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('blueprints', $data['data']);
        $this->assertNotEmpty($data['data']['blueprints']);
    }

    public function testBlueprintShowPage(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/blueprints/page')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('page', $data['data']['type']);
        $this->assertNotEmpty($data['data']['fields']);
    }
}
