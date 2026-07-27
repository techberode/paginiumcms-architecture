<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class JobsControllerPrivilegedDeployTest extends TestCase
{
    public function testAdminCannotUpdateSystemDeployJob(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/jobs/system-deploy', [
                'name' => 'System code deploy',
                'handler' => 'system.deploy',
                'cron' => '* * * * *',
                'enabled' => true,
                'payload' => ['ref' => 'v2.1.0-beta.99'],
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminCannotRunSystemDeployJob(): void
    {
        $this->loginAsAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/jobs/system-deploy/run', [])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSuperAdminCanRunSystemDeployWhenDisabled(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => false,
        ]));

        $this->loginAsSuperAdminUser();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/jobs/system-deploy/run', [])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('testing_skipped', $data['data']['result']['reason'] ?? null);
    }

    public function testAdminUpdateDoesNotPersistDeployRefInRegistry(): void
    {
        $registry = $this->container()->get(JobRegistryStore::class);
        $before = $registry->find('system-deploy');
        $this->assertNotNull($before);

        $this->loginAsSuperAdminUser();

        $this->handleRequest(
            $this->createJsonRequest('PUT', '/api/admin/jobs/system-deploy', [
                'name' => 'System code deploy',
                'handler' => 'system.deploy',
                'cron' => '0 0 1 1 *',
                'enabled' => true,
                'payload' => ['ref' => 'v2.1.0-beta.99'],
            ])
        );

        $after = $registry->find('system-deploy');
        $this->assertNotNull($after);
        $this->assertSame([], $after['payload']);
    }
}
