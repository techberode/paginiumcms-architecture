<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class ThemeStudioControllerTest extends TestCase
{
    public function testListFilesRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/files')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUserRoleCannotReadThemeFiles(): void
    {
        $userData = $this->createTestUser();
        $this->loginTestUser($userData['email'], $userData['password']);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/files')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSuperAdminCanListBundledThemeFiles(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/files')
        );
        $this->assertSame(200, $response->getStatusCode());

        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertSame('clean-journal', $payload['data']['themeId']);
        $this->assertIsArray($payload['data']['files']);

        $paths = array_map(
            static fn (array $item): string => $item['relativePath'],
            $payload['data']['files']
        );
        $this->assertContains('theme.json', $paths);
        $this->assertContains('templates/default.html', $paths);
    }

    public function testSuperAdminCanReadThemeFile(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/file?path=theme.json')
        );
        $this->assertSame(200, $response->getStatusCode());

        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success']);
        $this->assertSame('theme.json', $payload['data']['relativePath']);
        $this->assertSame('json', $payload['data']['language']);
        $this->assertStringContainsString('"id": "clean-journal"', $payload['data']['content']);
    }

    public function testPathTraversalIsRejected(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest(
                'GET',
                '/api/admin/themes/clean-journal/file?path=' . rawurlencode('../../etc/passwd')
            )
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testMissingThemeReturns404(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/not-installed-theme/files')
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMissingFileReturns404(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/themes/clean-journal/file?path=templates/missing.html')
        );

        $this->assertSame(404, $response->getStatusCode());
    }
}
