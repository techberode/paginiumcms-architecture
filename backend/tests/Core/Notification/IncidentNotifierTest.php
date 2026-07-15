<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Notification;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class IncidentNotifierTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/paginium_incident_' . uniqid();
        mkdir($this->baseDir . '/data', 0777, true);
        chdir($this->baseDir);
    }

    protected function tearDown(): void
    {
        chdir(sys_get_temp_dir());
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testSkipsWhenAlertsDisabled(): void
    {
        $repo = $this->makeRepo();
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->never())->method('send');

        $service = new NotificationService();
        $service->addAdapter('email', $adapter);

        $notifier = new IncidentNotifier($repo, $service);
        $notifier->notifyFailedLogin('user@example.com', '127.0.0.1');
    }

    public function testNotifiesOnFailedLoginWhenEnabled(): void
    {
        $repo = $this->makeRepo();
        $repo->setGroup('monitoring', [
            'alertsEnabled' => true,
            'notifyFailedLogin' => true,
            'minSeverity' => 'warning',
            'alertEmail' => 'alerts@example.com',
        ]);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('send')
            ->with(
                'alerts@example.com',
                $this->stringContains('Failed login'),
                $this->stringContains('user@example.com'),
                $this->callback(fn (array $opts) => ($opts['event'] ?? '') === 'auth.failed_login')
            )
            ->willReturn(true);

        $service = new NotificationService();
        $service->addAdapter('email', $adapter);

        $notifier = new IncidentNotifier($repo, $service);
        $notifier->notifyFailedLogin('user@example.com', '10.0.0.1');
    }

    private function makeRepo(): SettingsRepository
    {
        $validator = new FileValidator($this->baseDir);

        return new SettingsRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new Validator(),
            'data/settings.json'
        );
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
