<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Navigation;

use PaginiumCMS\Tests\Http\TestCase;

class NavigationControllerTest extends TestCase
{
    public function testGetNavigationIsPublic(): void
    {
        $request = $this->createJsonRequest('GET', '/api/navigation');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertNotEmpty($data['data']);
    }

    public function testUpdateNavigationRequiresAuth(): void
    {
        $request = $this->createJsonRequest('PUT', '/api/admin/navigation', [
            'items' => [
                ['label' => 'Home', 'path' => '/', 'order' => 0],
            ],
        ]);
        $response = $this->handleRequest($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testUpdateNavigationAsAdmin(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertEquals(200, $login['response']->getStatusCode());

        $request = $this->createJsonRequest('PUT', '/api/admin/navigation', [
            'items' => [
                ['label' => 'Custom Home', 'path' => '/', 'order' => 0],
                ['label' => 'Blog', 'path' => '/blog', 'order' => 1],
            ],
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('Custom Home', $data['data'][0]['label']);
    }
}
