<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Services\DebugLogger;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PHPUnit\Framework\TestCase;

class DebugLoggerTest extends TestCase
{
    private DebugLogger $debugLogger;
    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        // Jednoduché createMock – PHPUnit automaticky implementuje všetky metódy
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->debugLogger = new DebugLogger(
            $this->mockLogger,
            ['enabled' => true, 'level' => 'INFO', 'show_in_ui' => true, 'log_to_file' => true]
        );
    }

    public function testDebugWhenEnabled(): void
    {
        $this->mockLogger->expects($this->once())->method('debug');
        $this->debugLogger->debug('Test message', ['key' => 'value']);
    }

    public function testDebugWhenDisabled(): void
    {
        $debugLogger = new DebugLogger($this->mockLogger, ['enabled' => false, 'log_to_file' => true]);
        $this->mockLogger->expects($this->never())->method('debug');
        $debugLogger->debug('Test message');
    }

    public function testDebugWithLevelFiltering(): void
    {
        $debugLogger = new DebugLogger($this->mockLogger, ['enabled' => true, 'level' => 'ERROR', 'log_to_file' => false]);
        $this->mockLogger->expects($this->never())->method('debug');
        $debugLogger->debug('Test message', [], 'INFO');
    }

    public function testInfoMethod(): void
    {
        $this->mockLogger->expects($this->once())->method('debug');
        $this->debugLogger->info('Info message');
    }

    public function testWarningMethod(): void
    {
        $debugLogger = new DebugLogger(
            $this->mockLogger,
            ['enabled' => true, 'level' => 'WARNING', 'log_to_file' => true]
        );
        $this->mockLogger->expects($this->once())->method('debug');
        $debugLogger->warning('Warning message');
    }

    public function testErrorMethod(): void
    {
        $debugLogger = new DebugLogger(
            $this->mockLogger,
            ['enabled' => true, 'level' => 'ERROR', 'log_to_file' => true]
        );
        $this->mockLogger->expects($this->once())->method('debug');
        $debugLogger->error('Error message');
    }

    public function testSetEnabled(): void
    {
        $this->assertTrue($this->debugLogger->isEnabled());
        $this->debugLogger->setEnabled(false);
        $this->assertFalse($this->debugLogger->isEnabled());
    }

    public function testSetLevel(): void
    {
        $this->assertEquals('INFO', $this->debugLogger->getLevel());
        $this->debugLogger->setLevel('ERROR');
        $this->assertEquals('ERROR', $this->debugLogger->getLevel());
    }

    public function testSetShowInUi(): void
    {
        $this->assertTrue($this->debugLogger->isShowInUi());
        $this->debugLogger->setShowInUi(false);
        $this->assertFalse($this->debugLogger->isShowInUi());
    }

    public function testUiNotifications(): void
    {
        $_SESSION = [];
        $debugLogger = new DebugLogger($this->mockLogger, ['enabled' => true, 'show_in_ui' => true, 'log_to_file' => false]);
        $debugLogger->debug('Test UI notification', ['key' => 'value']);

        $notifications = $debugLogger->getUiNotifications();
        $this->assertNotEmpty($notifications);
        $this->assertEquals('Test UI notification', $notifications[0]['message']);
    }

    public function testClearUiNotifications(): void
    {
        $_SESSION['_debug_notifications'] = [['message' => 'Test']];
        $this->debugLogger->clearUiNotifications();
        $this->assertEmpty($this->debugLogger->getUiNotifications());
    }
}
