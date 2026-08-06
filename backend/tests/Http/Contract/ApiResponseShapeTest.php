<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Contract;

use PaginiumCMS\Tests\Http\TestCase;

/**
 * Asserts unified API response envelopes (Iteration 21).
 */
final class ApiResponseShapeTest extends TestCase
{
    public function testHealthSuccessShape(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/health'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('success', $data);
        $this->assertIsBool($data['success']);
    }

    public function testPagesListSuccessShape(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/pages?page=1&per_page=5'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(1, $data['meta']['page']);
        $this->assertSame(5, $data['meta']['per_page']);
        $this->assertArrayHasKey('total', $data['meta']);
        $this->assertArrayHasKey('total_pages', $data['meta']);
    }

    public function testUnknownPageErrorShape(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/pages/missing-' . uniqid('', true))
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertIsString($data['error']);
        $this->assertArrayNotHasKey('data', $data);
    }

    public function testLoginLegacySuccessShape(): void
    {
        $user = $this->createTestUser();
        $login = $this->loginTestUser($user['email'], $user['password']);
        $data = $login['data'];

        $this->assertSame(200, $login['response']->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('user', $data);
        $this->assertIsArray($data['user']);
    }

    public function testLoginErrorShape(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'WrongP@ssw0rd123!',
        ]));
        $data = $this->getJsonResponse($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
    }

    public function testPublicSettingsShape(): void
    {
        $this->loginAsAdminUser();
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/settings/public'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('contact', $data['data']);
        $this->assertArrayHasKey('subjects', $data['data']['contact']);
        $this->assertArrayHasKey('allowCustomSubject', $data['data']['contact']);
        $this->assertArrayHasKey('company', $data['data']);
        $this->assertArrayHasKey('showOnContactPage', $data['data']['company']);
        $this->assertArrayHasKey('mapEmbedUrl', $data['data']['company']);
        $this->assertArrayHasKey('appearance', $data['data']);
        $this->assertArrayHasKey('colorScheme', $data['data']['appearance']);
        $this->assertArrayHasKey('mode', $data['data']['appearance']);
        $this->assertArrayHasKey('allowUserToggle', $data['data']['appearance']);
        $this->assertContains($data['data']['appearance']['colorScheme'], [
            'indigo-classic',
            'ocean-slate',
            'forest-sage',
            'sunset-rose',
            'mono-zinc',
        ]);
        $this->assertContains($data['data']['appearance']['mode'], ['light', 'dark', 'system']);
        $this->assertArrayHasKey('layout', $data['data']);
        $this->assertArrayHasKey('cmsInfo', $data['data']);
        $this->assertArrayHasKey('version', $data['data']['cmsInfo']);
        $this->assertIsString($data['data']['cmsInfo']['version']);
        $this->assertArrayHasKey('builderMode', $data['data']['layout']);
        $this->assertArrayHasKey('defaultTemplate', $data['data']['layout']);
        $this->assertContains($data['data']['layout']['builderMode'], [
            'templates',
            'shortcodes',
            'outline',
            'developer',
        ]);
        $this->assertContains($data['data']['layout']['defaultTemplate'], [
            'single',
            'hero-content',
            'two-column',
            'landing',
            'blog-article',
        ]);
    }

    public function testValidationRulesShape(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/validation/rules'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    public function testMediaListRequiresAuthErrorShape(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/media'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
    }

    public function testBackupListSuccessShape(): void
    {
        $this->loginAsAdminUser();
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/backups'));
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }
}
