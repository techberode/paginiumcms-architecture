<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\Developer\DevTokenGenerator;
use PaginiumCMS\Core\Developer\DevTokenRegistry;
use PaginiumCMS\Tests\Http\TestCase;

final class DeveloperControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('APP_DEBUG=true');
        $_ENV['APP_DEBUG'] = 'true';
    }

    public function testStatusRequiresAuth(): void
    {
        $request = $this->createJsonRequest('GET', '/api/admin/developer/status');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testStatusReturnsGatePayload(): void
    {
        $this->loginAsAdminUser();

        $request = $this->createJsonRequest('GET', '/api/admin/developer/status');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('feature_available', $data['data']);
        $this->assertArrayHasKey('unlocked', $data['data']);
    }

    public function testUnlockWithRegisteredDevToken(): void
    {
        $this->loginAsAdminUser();

        $generator = $this->app->getContainer()->get(DevTokenGenerator::class);
        $registry = $this->app->getContainer()->get(DevTokenRegistry::class);
        $issued = $generator->generate('phpunit');
        $registry->registerFromToken($generator, $issued['token']);

        $request = $this->createJsonRequest('POST', '/api/admin/developer/unlock', [
            'token' => $issued['token'],
        ]);
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['unlocked']);
    }
}
