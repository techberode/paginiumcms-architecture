<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Handlers;

use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\Analytics\Services\AnalyticsRetentionService;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Logging\Services\LogRetentionService;
use PaginiumCMS\Core\Scheduler\Handlers\MaintenanceCleanupHandler;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class MaintenanceCleanupHandlerTest extends TestCase
{
    public function testHandlePurgesAnalyticsAndLogs(): void
    {
        vfsStream::setup('storage');

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('storage'));

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['analytics', ['retentionDays' => 90]],
            ['logging', ['retentionDays' => 30]],
        ]);

        $validator = new FileValidator(vfsStream::url('storage'));
        $fileReader = new FileReader($validator);
        $fileWriter = new FileWriter($validator);

        $handler = new MaintenanceCleanupHandler(
            new AnalyticsRetentionService($reader, $settings),
            new LogRetentionService($fileReader, $fileWriter, $settings)
        );
        $result = $handler->handle();

        $this->assertTrue($result->success);
        $this->assertStringContainsString('Analytics:', $result->message);
        $this->assertSame('maintenance.cleanup', $handler->key());
    }
}
