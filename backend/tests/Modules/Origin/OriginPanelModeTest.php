<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Origin;

use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Origin\Services\OriginPanelMode;
use PHPUnit\Framework\TestCase;

final class OriginPanelModeTest extends TestCase
{
    private string|false|null $previousOriginPanel = null;
    private string|false|null $previousAllowedHosts = null;
    private string|false|null $previousAppUrl = null;
    private string|false|null $previousDemoMode = null;

    protected function setUp(): void
    {
        $this->previousOriginPanel = getenv('ORIGIN_PANEL');
        $this->previousAllowedHosts = getenv('ORIGIN_PANEL_ALLOWED_HOSTS');
        $this->previousAppUrl = getenv('APP_URL');
        $this->previousDemoMode = getenv('DEMO_MODE');
    }

    protected function tearDown(): void
    {
        $this->restoreEnv('ORIGIN_PANEL', $this->previousOriginPanel);
        $this->restoreEnv('ORIGIN_PANEL_ALLOWED_HOSTS', $this->previousAllowedHosts);
        $this->restoreEnv('APP_URL', $this->previousAppUrl);
        $this->restoreEnv('DEMO_MODE', $this->previousDemoMode);
    }

    public function testIsInactiveWhenEnvFlagFalse(): void
    {
        putenv('ORIGIN_PANEL=false');
        $_ENV['ORIGIN_PANEL'] = 'false';

        $this->assertFalse(OriginPanelMode::isActive('localhost'));
    }

    public function testIsActiveForAllowlistedHost(): void
    {
        putenv('ORIGIN_PANEL=true');
        $_ENV['ORIGIN_PANEL'] = 'true';
        putenv('ORIGIN_PANEL_ALLOWED_HOSTS=localhost,paginiumcms.com');
        $_ENV['ORIGIN_PANEL_ALLOWED_HOSTS'] = 'localhost,paginiumcms.com';
        putenv('DEMO_MODE=false');
        unset($_ENV['DEMO_MODE']);

        $this->assertTrue(OriginPanelMode::isActive('localhost'));
        $this->assertTrue(OriginPanelMode::isActive('paginiumcms.com'));
        $this->assertFalse(OriginPanelMode::isActive('customer.example.com'));
    }

    public function testIsInactiveWhenDemoModeEnabled(): void
    {
        putenv('ORIGIN_PANEL=true');
        $_ENV['ORIGIN_PANEL'] = 'true';
        putenv('DEMO_MODE=true');
        $_ENV['DEMO_MODE'] = 'true';
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';

        $this->assertFalse(OriginPanelMode::isActive('localhost'));
    }

    public function testAllowedHostsIncludeAppUrlHost(): void
    {
        putenv('APP_URL=https://dev.paginiumcms.test:8080');
        $_ENV['APP_URL'] = 'https://dev.paginiumcms.test:8080';
        putenv('ORIGIN_PANEL_ALLOWED_HOSTS');
        unset($_ENV['ORIGIN_PANEL_ALLOWED_HOSTS']);

        $hosts = OriginPanelMode::allowedHosts();

        $this->assertContains('dev.paginiumcms.test', $hosts);
    }

    public function testAllowedHostsMergeAppUrlWhenExplicitAllowlistSet(): void
    {
        putenv('APP_URL=https://192.168.10.26:8081');
        $_ENV['APP_URL'] = 'https://192.168.10.26:8081';
        putenv('ORIGIN_PANEL_ALLOWED_HOSTS=localhost,127.0.0.1');
        $_ENV['ORIGIN_PANEL_ALLOWED_HOSTS'] = 'localhost,127.0.0.1';

        $hosts = OriginPanelMode::allowedHosts();

        $this->assertContains('192.168.10.26', $hosts);
        $this->assertContains('localhost', $hosts);
    }

    public function testIsActiveForPrivateLanHostInDevelopment(): void
    {
        putenv('ORIGIN_PANEL=true');
        $_ENV['ORIGIN_PANEL'] = 'true';
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';
        putenv('ORIGIN_PANEL_ALLOWED_HOSTS=localhost,127.0.0.1');
        $_ENV['ORIGIN_PANEL_ALLOWED_HOSTS'] = 'localhost,127.0.0.1';
        putenv('DEMO_MODE=false');
        unset($_ENV['DEMO_MODE']);

        $this->assertTrue(OriginPanelMode::isActive('192.168.10.26'));
    }

    public function testIsInactiveForPrivateLanHostInProduction(): void
    {
        putenv('ORIGIN_PANEL=true');
        $_ENV['ORIGIN_PANEL'] = 'true';
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        putenv('ORIGIN_PANEL_ALLOWED_HOSTS=localhost,127.0.0.1');
        $_ENV['ORIGIN_PANEL_ALLOWED_HOSTS'] = 'localhost,127.0.0.1';

        $this->assertFalse(OriginPanelMode::isActive('192.168.10.26'));
    }

    private function restoreEnv(string $key, string|false|null $previous): void
    {
        if ($previous === false || $previous === null || $previous === '') {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
            return;
        }

        putenv($key . '=' . $previous);
        $_ENV[$key] = $previous;
        $_SERVER[$key] = $previous;
    }
}
