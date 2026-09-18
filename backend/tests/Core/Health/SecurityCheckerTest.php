<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Health;

use PaginiumCMS\Core\Health\Models\HealthStatus;
use PaginiumCMS\Core\Health\Services\Checkers\SecurityChecker;
use PaginiumCMS\Core\Health\Services\HealthRuntimeContext;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SecurityCheckerTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;
        putenv('APP_ENV=production');
        putenv('APP_DEBUG=false');
        $_ENV['APP_ENV'] = 'production';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        HealthRuntimeContext::clear();
        parent::tearDown();
    }

    public function testHttpsPassWithTrustedForwardedProtoAndSiteUrl(): void
    {
        $_SERVER = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://example.com/api/admin/dashboard/overview', $_SERVER)
            ->withHeader('X-Forwarded-Proto', 'https');
        HealthRuntimeContext::bindFromRequest($request);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(static function (string $key) {
            if ($key === 'general.siteUrl') {
                return 'https://example.com';
            }

            return null;
        });

        $checker = new SecurityChecker($settings);
        $result = $checker->check();

        $this->assertTrue($result->getData()['https'] ?? false);
        $this->assertStringNotContainsString('HTTPS', $result->getMessage());
    }

    public function testHttpsWarnWhenPlainHttpInProduction(): void
    {
        $_SERVER = ['REMOTE_ADDR' => '127.0.0.1'];
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'http://127.0.0.1:8080/api/admin/dashboard/overview', $_SERVER);
        HealthRuntimeContext::bindFromRequest($request);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturn('https://example.com');

        $checker = new SecurityChecker($settings);
        $result = $checker->check();

        $this->assertSame(HealthStatus::STATUS_WARN, $result->getStatus());
        $this->assertStringContainsString('HTTPS', $result->getMessage());
    }
}
