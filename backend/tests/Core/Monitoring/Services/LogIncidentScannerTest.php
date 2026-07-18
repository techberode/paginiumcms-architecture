<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Monitoring\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Monitoring\Services\LogIncidentScanner;
use PaginiumCMS\Core\Monitoring\Services\SchedulerStateStore;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PHPUnit\Framework\TestCase;

final class LogIncidentScannerTest extends TestCase
{
    public function testScanSkipsWhenBothIncidentTypesDisabled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('monitoring')->willReturn([
            'notifyLogErrors' => false,
            'notifyLogWarnings' => false,
        ]);

        $writer = $this->createMock(LogWriterInterface::class);
        $writer->expects($this->never())->method('readSince');

        $scanner = new LogIncidentScanner(
            $settings,
            $writer,
            IncidentNotifierTestFactory::create($settings, $this->createMock(NotificationService::class)),
            $this->makeStateStore()
        );

        $this->assertSame(['notified' => 0, 'scanned' => 0], $scanner->scan());
    }

    public function testScanNotifiesNewErrorLogEntry(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'monitoring') {
                return [
                    'notifyLogErrors' => true,
                    'notifyLogWarnings' => false,
                    'logIncidentConnector' => 'email',
                ];
            }

            return ['adminEmail' => 'admin@example.com'];
        });

        $writer = $this->createMock(LogWriterInterface::class);
        $writer->expects($this->once())
            ->method('readSince')
            ->willReturn([
                [
                    'id' => 'log-1',
                    'severity' => LogSeverity::ERROR,
                    'message' => 'Disk full',
                    'category' => 'storage',
                    'timestamp' => date('c'),
                ],
            ]);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->method('getAdapters')->willReturn(['email']);
        $notifications->expects($this->once())->method('send')->willReturn(true);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $fileWriter = $this->createMock(FileWriterInterface::class);
        $fileWriter->expects($this->atLeastOnce())->method('write');

        $scanner = new LogIncidentScanner(
            $settings,
            $writer,
            IncidentNotifierTestFactory::create($settings, $notifications),
            new SchedulerStateStore($reader, $fileWriter)
        );

        $result = $scanner->scan();

        $this->assertSame(1, $result['notified']);
        $this->assertSame(1, $result['scanned']);
    }

    private function makeStateStore(): SchedulerStateStore
    {
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $writer = $this->createMock(FileWriterInterface::class);

        return new SchedulerStateStore($reader, $writer);
    }
}
