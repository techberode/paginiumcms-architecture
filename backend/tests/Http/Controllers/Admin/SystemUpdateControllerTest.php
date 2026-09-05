<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;
use Slim\Psr7\Factory\StreamFactory;

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
        $this->assertArrayHasKey('deploy_readiness', $data['data']);
        $this->assertIsArray($data['data']['deploy_readiness']);
        $this->assertArrayHasKey('blockers', $data['data']['deploy_readiness']);
    }

    public function testRunForbiddenWhenDeployDisabled(): void
    {
        $login = $this->loginAsSuperAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => false,
            'allowDeployTags' => true,
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/run', [
                'ref' => 'v2.1.0-beta.12',
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRunQueuesJobWhenEnabled(): void
    {
        $login = $this->loginAsSuperAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
            'allowDeployMain' => false,
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/run', [
                'ref' => 'v2.1.0-beta.12',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode(), (string) json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['queued']);
        $this->assertSame('v2.1.0-beta.12', $data['data']['ref']);
    }

    public function testRunUsesParsedBodyWhenStreamIsEmpty(): void
    {
        $this->loginAsSuperAdminUser();

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
            'allowDeployMain' => false,
        ]));

        $request = $this->createJsonRequest('POST', '/api/admin/system/update/run', null);
        $request = $request->withBody((new StreamFactory())->createStream(''));
        $request = $request->withParsedBody(['ref' => 'v2.1.0-beta.39']);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode(), (string) json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($data['success']);
        $this->assertSame('v2.1.0-beta.39', $data['data']['ref']);
    }

    public function testRunEmptyRefRequiresTagWhenBranchDeployDisabled(): void
    {
        $this->loginAsSuperAdminUser();

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
            'allowDeployMain' => false,
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/run', [])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('release tag', strtolower((string) ($data['error'] ?? '')));
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
        $this->assertArrayHasKey('deploy_readiness', $data['data']);
        $this->assertContains(
            $data['data']['update']['status'] ?? '',
            ['current', 'update_available', 'unknown']
        );
        if (is_array($data['data']['remote']['compare'] ?? null)) {
            $this->assertArrayHasKey('commits', $data['data']['remote']['compare']);
        }
    }
}
