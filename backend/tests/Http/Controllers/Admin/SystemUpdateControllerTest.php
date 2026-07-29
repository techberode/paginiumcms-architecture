<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class SystemUpdateControllerTest extends TestCase
{
    public function testStatusRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/system/update/status')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testStatusForbiddenForAdmin(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/system/update/status')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testStatusForSuperAdmin(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/system/update/status')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('app_version', $data['data']);
        $this->assertArrayHasKey('git', $data['data']);
        $this->assertArrayHasKey('job_registered', $data['data']);
    }

    public function testRunForbiddenWhenDeployDisabled(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => false,
            'allowDeployTags' => true,
        ]));

        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/run', [
                'ref' => 'v2.1.0-beta.12',
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRunQueuesJobWhenEnabled(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
            'allowDeployMain' => false,
        ]));

        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/run', [
                'ref' => 'v2.1.0-beta.12',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['queued']);
        $this->assertSame('v2.1.0-beta.12', $data['data']['ref']);
    }

    public function testCheckForbiddenForAdmin(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/check', [])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCheckReturnsUpdateEnvelopeForSuperAdmin(): void
    {
        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/check', [])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('git', $data['data']);
        $this->assertArrayHasKey('remote', $data['data']);
        $this->assertArrayHasKey('update', $data['data']);
        $this->assertContains(
            $data['data']['update']['status'] ?? '',
            ['current', 'update_available', 'unknown']
        );
        if (is_array($data['data']['remote']['compare'] ?? null)) {
            $this->assertArrayHasKey('commits', $data['data']['remote']['compare']);
        }
    }
}
