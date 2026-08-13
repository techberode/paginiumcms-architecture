<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Auth;

use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Tests\Http\TestCase;

final class DemoLoginIsolationTest extends TestCase
{
    public function testProductionEmailRejectedOnDemoInstanceWithClearMessage(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
        $_SERVER['DEMO_MODE'] = 'true';

        try {
            $this->rebootstrapApplication();

            $response = $this->handleRequest($this->createJsonRequest('POST', '/api/auth/login', [
                'email' => 'admin@mycompany.sk',
                'password' => 'Whatever123!',
            ]));

            $data = $this->getJsonResponse($response);

            $this->assertSame(401, $response->getStatusCode());
            $this->assertStringContainsString('demo inštancia', (string) ($data['error'] ?? ''));
        } finally {
            putenv('DEMO_MODE=false');
            $_ENV['DEMO_MODE'] = 'false';
            $_SERVER['DEMO_MODE'] = 'false';
            $this->rebootstrapApplication();
        }
    }

    public function testDemoEmailRejectedOnProductionInstanceWithClearMessage(): void
    {
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';
        $_SERVER['DEMO_MODE'] = 'false';

        try {
            $this->rebootstrapApplication();

            $response = $this->handleRequest($this->createJsonRequest('POST', '/api/auth/login', [
                'email' => DemoFixtures::ADMIN_EMAIL,
                'password' => DemoFixtures::ADMIN_PASSWORD,
            ]));

            $data = $this->getJsonResponse($response);

            $this->assertSame(401, $response->getStatusCode());
            $this->assertStringContainsString('DEMO_MODE=true', (string) ($data['error'] ?? ''));
        } finally {
            $this->rebootstrapApplication();
        }
    }

    public function testDemoGuardBlockDoesNotIncrementBruteForceLockout(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
        $_SERVER['DEMO_MODE'] = 'true';

        try {
            $this->rebootstrapApplication();

            for ($i = 0; $i < 6; ++$i) {
                $response = $this->handleRequest($this->createJsonRequest('POST', '/api/auth/login', [
                    'email' => 'admin@mycompany.sk',
                    'password' => 'Wrong123!',
                ]));
                $this->assertSame(401, $response->getStatusCode());
            }

            $response = $this->handleRequest($this->createJsonRequest('POST', '/api/auth/login', [
                'email' => 'admin@mycompany.sk',
                'password' => 'Wrong123!',
            ]));
            $data = $this->getJsonResponse($response);

            $this->assertSame(401, $response->getStatusCode());
            $this->assertStringNotContainsString('neúspešných pokusov', (string) ($data['error'] ?? ''));
        } finally {
            putenv('DEMO_MODE=false');
            $_ENV['DEMO_MODE'] = 'false';
            $_SERVER['DEMO_MODE'] = 'false';
            $this->rebootstrapApplication();
        }
    }
}
