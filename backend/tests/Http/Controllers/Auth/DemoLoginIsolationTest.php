<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Auth;

use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Tests\Http\TestCase;

final class DemoLoginIsolationTest extends TestCase
{
    private string $previousDemoMode = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousDemoMode = (string) (getenv('DEMO_MODE') ?: '');
    }

    protected function tearDown(): void
    {
        if ($this->previousDemoMode !== '') {
            putenv('DEMO_MODE=' . $this->previousDemoMode);
            $_ENV['DEMO_MODE'] = $this->previousDemoMode;
        } else {
            putenv('DEMO_MODE');
            unset($_ENV['DEMO_MODE']);
        }

        parent::tearDown();
    }

    public function testProductionEmailRejectedOnDemoInstanceWithClearMessage(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $this->app = require __DIR__ . '/../../../../bootstrap/app.php';

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@mycompany.sk',
            'password' => 'Whatever123!',
        ]));

        $data = $this->getJsonResponse($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('demo inštancia', (string) ($data['error'] ?? ''));
    }

    public function testDemoEmailRejectedOnProductionInstanceWithClearMessage(): void
    {
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';

        $this->app = require __DIR__ . '/../../../../bootstrap/app.php';

        $response = $this->handleRequest($this->createJsonRequest('POST', '/api/auth/login', [
            'email' => DemoFixtures::ADMIN_EMAIL,
            'password' => DemoFixtures::ADMIN_PASSWORD,
        ]));

        $data = $this->getJsonResponse($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('DEMO_MODE=true', (string) ($data['error'] ?? ''));
    }

    public function testDemoGuardBlockDoesNotIncrementBruteForceLockout(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $this->app = require __DIR__ . '/../../../../bootstrap/app.php';

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
    }
}
