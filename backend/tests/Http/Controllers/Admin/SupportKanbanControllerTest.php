<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class SupportKanbanControllerTest extends TestCase
{
    public function testIndexRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/support-kanban'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testIndexReturnsBoard(): void
    {
        $this->loginAsAdminUser();
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/support-kanban'));
        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success'] ?? false);
        $this->assertSame('support-board@1', $payload['data']['board']['schema'] ?? null);
        $this->assertIsArray($payload['data']['tickets'] ?? null);
        $this->assertIsArray($payload['data']['agents'] ?? null);
    }
}
