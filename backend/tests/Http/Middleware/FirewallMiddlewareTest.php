<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Security\Firewall\FirewallBanStore;
use PaginiumCMS\Core\Security\Firewall\FirewallIncidentLogger;
use PaginiumCMS\Core\Security\Firewall\FirewallScenarioRegistry;
use PaginiumCMS\Core\Security\Firewall\FirewallScanner;
use PaginiumCMS\Core\Security\Firewall\FirewallService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Middleware\FirewallMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

final class FirewallMiddlewareTest extends TestCase
{
    private string $basePath;
    private FirewallService $firewall;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_ENV=local');
        $_ENV['APP_ENV'] = 'local';

        $this->basePath = sys_get_temp_dir() . '/paginium-fw-mw-' . uniqid('', true);
        mkdir($this->basePath . '/data/security/firewall', 0777, true);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn($this->basePath);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'firewall') {
                return [
                    'enabled' => true,
                    'jailMinutes' => 15,
                    'maxRetries' => 3,
                    'permanentThreshold' => 3,
                    'jailMode' => 'forbidden',
                    'tarpitSeconds' => 0,
                    'logRetention' => 100,
                ];
            }

            return [];
        });

        $banStore = new FirewallBanStore($reader, $settings);
        $incidents = new FirewallIncidentLogger($reader, $settings);
        $this->firewall = new FirewallService(
            $settings,
            new FirewallScanner(new FirewallScenarioRegistry()),
            $banStore,
            $incidents
        );
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->basePath);
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        parent::tearDown();
    }

    public function testBannedIpReturns403(): void
    {
        $ip = '198.51.100.9';
        $this->firewall->manualBan($ip, false, 'manual');

        $middleware = new FirewallMiddleware($this->firewall);
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/blog', ['REMOTE_ADDR' => $ip])
            ->withHeader('User-Agent', 'Mozilla/5.0');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCleanIpPassesToHandler(): void
    {
        $middleware = new FirewallMiddleware($this->firewall);
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/health', ['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('User-Agent', 'Mozilla/5.0');

        $expected = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $response = $middleware->process($request, $handler);
        $this->assertSame($expected, $response);
    }

    public function testScenarioMatchReturns403(): void
    {
        $middleware = new FirewallMiddleware($this->firewall);
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/wp-login.php', ['REMOTE_ADDR' => '203.0.113.1'])
            ->withHeader('User-Agent', 'curl/8.0');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);
        $this->assertSame(403, $response->getStatusCode());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
