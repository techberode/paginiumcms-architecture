<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Modules\Demo\Services\DemoLoginGuard;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PHPUnit\Framework\TestCase;

final class DemoLoginGuardTest extends TestCase
{
    private string $previousDemoMode = '';

    protected function setUp(): void
    {
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
    }

    public function testBlocksProductionEmailOnDemoInstance(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $guard = new DemoLoginGuard(new DemoMode());
        $message = $guard->blockedLoginMessage('admin@mycompany.sk');

        $this->assertNotNull($message);
        $this->assertStringContainsString('demo inštancia', $message);
        $this->assertStringContainsString(DemoFixtures::ADMIN_EMAIL, $message);
    }

    public function testBlocksDemoEmailOnProductionInstance(): void
    {
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';

        $guard = new DemoLoginGuard(new DemoMode());
        $message = $guard->blockedLoginMessage(DemoFixtures::ADMIN_EMAIL);

        $this->assertNotNull($message);
        $this->assertStringContainsString('DEMO_MODE=true', $message);
    }

    public function testAllowsDemoEmailOnDemoInstance(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $guard = new DemoLoginGuard(new DemoMode());

        $this->assertNull($guard->blockedLoginMessage(DemoFixtures::ADMIN_EMAIL));
    }

    public function testFailedLoginMessageForWrongDemoPassword(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $guard = new DemoLoginGuard(new DemoMode());
        $message = $guard->failedLoginMessage(DemoFixtures::ADMIN_EMAIL, 'Neplatný email alebo heslo');

        $this->assertStringContainsString('Neplatné heslo demo účtu', $message);
    }
}
