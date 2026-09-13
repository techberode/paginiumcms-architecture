<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;
use Slim\Psr7\Factory\StreamFactory;

final class SystemUpdateControllerTest extends TestCase
{
    /** @var list<string> */
    private array $tempStackDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempStackDirs as $dir) {
            if (is_file($dir . '/stack.sh')) {
                unlink($dir . '/stack.sh');
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
        $this->tempStackDirs = [];

        parent::tearDown();
    }

    private function configureDeployReady(): void
    {
        $stackDir = sys_get_temp_dir() . '/paginium-deploy-test-' . uniqid('', true);
        mkdir($stackDir, 0777, true);
        file_put_contents($stackDir . '/stack.sh', "#!/usr/bin/env bash\n");
        chmod($stackDir . '/stack.sh', 0755);
        $this->tempStackDirs[] = $stackDir;

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
            'allowDeployMain' => false,
            'stackDir' => $stackDir,
            'backendPort' => '8089',
        ]));
    }

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

        $this->configureDeployReady();

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
        $this->configureDeployReady();

        $request = $this->createJsonRequest('POST', '/api/admin/system/update/run', null);
        $request = $request->withBody((new StreamFactory())->createStream(''));
        $request = $request->withParsedBody(['ref' => 'v2.1.0-beta.39']);

        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode(), (string) json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($data['success']);
        $this->assertSame('v2.1.0-beta.39', $data['data']['ref']);
    }

    public function testRunAcceptsSemverTagWithoutVPrefix(): void
    {
        $this->loginAsSuperAdminUser();
        $this->configureDeployReady();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/run', [
                'ref' => '2.1.0-beta.71',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode(), (string) json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($data['success']);
        $this->assertSame('v2.1.0-beta.71', $data['data']['ref']);
    }

    public function testRunReturns503WhenStackDirMissing(): void
    {
        $this->loginAsSuperAdminUser();

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
            'allowDeployMain' => false,
            'stackDir' => '',
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/system/update/run', [
                'ref' => 'v2.1.0-beta.12',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('stack_dir_missing', (string) ($data['error'] ?? ''));
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
