<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Notification;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class IncidentNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('APP_ENV=testing');
    }

    protected function tearDown(): void
    {
        putenv('APP_ENV=testing');
        parent::tearDown();
    }

    public function testSkipsWhenAlertsDisabled(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->never())->method('send');

        $notifier = $this->makeNotifier(['alertsEnabled' => false], $adapter);
        $notifier->notifyLoginLockout('user@example.com', '127.0.0.1');
    }

    public function testSkipsExampleComTestEmailsEvenWhenAlertsEnabled(): void
    {
        putenv('APP_ENV=production');
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->never())->method('send');

        $notifier = $this->makeNotifier([
            'alertsEnabled' => true,
            'notifyFailedLogin' => true,
            'minSeverity' => 'warning',
            'alertEmail' => 'alerts@example.com',
        ], $adapter);
        $notifier->notifyLoginLockout('test_6a5b58219cae7@example.com', '127.0.0.1');
    }

    public function testNotifiesOnLockoutWhenEnabled(): void
    {
        putenv('APP_ENV=production');
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('send')
            ->with(
                'alerts@example.com',
                $this->stringContains('Login lockout'),
                $this->stringContains('admin@test.com'),
                $this->callback(fn (array $opts) => ($opts['event'] ?? '') === 'auth.failed_login')
            )
            ->willReturn(true);

        $notifier = $this->makeNotifier([
            'alertsEnabled' => true,
            'notifyFailedLogin' => true,
            'minSeverity' => 'warning',
            'alertEmail' => 'alerts@example.com',
        ], $adapter);
        $notifier->notifyLoginLockout('admin@test.com', '10.0.0.1');
    }

    public function testThrottlesDuplicateLockoutAlerts(): void
    {
        putenv('APP_ENV=production');
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())->method('send')->willReturn(true);

        $notifier = $this->makeNotifier([
            'alertsEnabled' => true,
            'notifyFailedLogin' => true,
            'minSeverity' => 'warning',
            'alertEmail' => 'alerts@example.com',
        ], $adapter);
        $notifier->notifyLoginLockout('admin@test.com', '10.0.0.1');
        $notifier->notifyLoginLockout('admin@test.com', '10.0.0.1');
    }

    /**
     * @param array<string, mixed> $monitoring
     */
    private function makeNotifier(array $monitoring, ?AdapterInterface $adapter = null): IncidentNotifier
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) use ($monitoring): array {
            return match ($group) {
                'monitoring' => $monitoring,
                'general' => ['adminEmail' => ''],
                default => [],
            };
        });

        $service = new NotificationService();
        if ($adapter !== null) {
            $service->addAdapter('email', $adapter);
        }

        return new IncidentNotifier($settings, $service, new CacheManager(new MemoryDriver(), 'test_'));
    }
}
