<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;
use PaginiumCMS\Tests\Support\TestStorageCleaner;

final class TeamControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestStorageCleaner::purgeTestTeams();
    }

    public function testIndexAllowedForAdmin(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/teams')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']['teams'] ?? null);
    }

    public function testCreateForbiddenForAdmin(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/teams', [
                'name' => 'Marketing',
                'type' => 'custom',
                'memberUserIds' => [],
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreateAllowedForSuperAdmin(): void
    {
        $this->loginAsSuperAdminUser();

        $name = 'Partners-' . uniqid('', true);
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/teams', [
                'name' => $name,
                'type' => 'external',
                'memberUserIds' => [],
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame($name, $data['data']['team']['name'] ?? '');
    }

    public function testUpdateMembersForbiddenForAdmin(): void
    {
        $this->loginAsSuperAdminUser();
        $create = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/teams', [
                'name' => 'Desk-' . uniqid('', true),
                'type' => 'support',
                'memberUserIds' => [],
            ])
        );
        $created = $this->getJsonResponse($create);
        $teamId = (string) ($created['data']['team']['id'] ?? '');
        $this->assertNotSame('', $teamId);

        $this->loginAsAdminUser();
        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/teams/' . $teamId, [
                'memberUserIds' => [],
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
