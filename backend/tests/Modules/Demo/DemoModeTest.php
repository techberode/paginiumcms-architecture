<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Demo;

use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PHPUnit\Framework\TestCase;

final class DemoModeTest extends TestCase
{
    private string $previousDemoMode = '';
    private string|false|null $previousSessionLifetime = null;
    private string|false|null $previousAppEnv = null;

    protected function setUp(): void
    {
        $this->previousDemoMode = (string) (getenv('DEMO_MODE') ?: '');
        $this->previousSessionLifetime = getenv('SESSION_LIFETIME');
        $this->previousAppEnv = getenv('APP_ENV');
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

        if ($this->previousSessionLifetime === false) {
            putenv('SESSION_LIFETIME');
            unset($_ENV['SESSION_LIFETIME']);
        } elseif ($this->previousSessionLifetime !== null) {
            putenv('SESSION_LIFETIME=' . $this->previousSessionLifetime);
            $_ENV['SESSION_LIFETIME'] = $this->previousSessionLifetime;
        }

        if ($this->previousAppEnv === false) {
            putenv('APP_ENV');
            unset($_ENV['APP_ENV']);
        } elseif ($this->previousAppEnv !== null) {
            putenv('APP_ENV=' . $this->previousAppEnv);
            $_ENV['APP_ENV'] = $this->previousAppEnv;
        }
    }

    public function testResolveContentBasePathUsesDemoWhenEnabled(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';

        $path = DemoMode::resolveContentBasePath('/var/app/storage/app');

        $this->assertSame('/var/app/storage/app/demo', $path);
    }

    public function testResolveContentBasePathUsesProductionWhenDisabled(): void
    {
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';

        $path = DemoMode::resolveContentBasePath('/var/app/storage/app');

        $this->assertSame('/var/app/storage/app/content', $path);
    }

    public function testSessionLifetimeDefaultIsEightHoursWhenDemoDisabled(): void
    {
        putenv('DEMO_MODE=false');
        unset($_ENV['DEMO_MODE']);
        putenv('SESSION_LIFETIME');
        unset($_ENV['SESSION_LIFETIME']);
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';

        $mode = new DemoMode();

        $this->assertSame(28800, $mode->sessionLifetimeSeconds());
    }

    public function testDemoModeIsDisabledOnProductionEvenWhenEnvFlagTrue(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        $this->assertTrue(DemoMode::isMisconfiguredProductionDemo());
        $this->assertFalse(DemoMode::isEnabledFromEnv());
        $this->assertSame('/var/app/storage/app/content', DemoMode::resolveContentBasePath('/var/app/storage/app'));

        $mode = new DemoMode();
        $this->assertFalse($mode->isEnabled());
    }

    public function testDemoModeRemainsEnabledOnNonProduction(): void
    {
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';

        $this->assertFalse(DemoMode::isMisconfiguredProductionDemo());
        $this->assertTrue(DemoMode::isEnabledFromEnv());
    }
}
