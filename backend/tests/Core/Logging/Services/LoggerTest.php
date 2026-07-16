<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Services\Logger;
use PaginiumCMS\Core\Logging\Services\LogWriter;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class LoggerTest extends TestCase
{
    private LogWriter $writer;
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $structure = [
            'logs' => [
                'app' => [],
            ],
        ];

        $root = vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $reader = $this->createMock(FileReaderInterface::class);
        $writer = $this->createMock(FileWriterInterface::class);

        $this->writer = new LogWriter($reader, $writer, $this->root . '/logs/app');
    }

    public function testInfo(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('write');

        $logger = new Logger($this->writer, 'test');
        $logger->info('Test info message');
    }

    public function testWarning(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('write');

        $logger = new Logger($this->writer, 'test');
        $logger->warning('Test warning message');
    }

    public function testError(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('write');

        $logger = new Logger($this->writer, 'test');
        $logger->error('Test error message');
    }

    public function testCritical(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('write');

        $logger = new Logger($this->writer, 'test');
        $logger->critical('Test critical message');
    }

    public function testDebug(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('write');

        $logger = new Logger($this->writer, 'test');
        $logger->debug('Test debug message');
    }

    public function testLog(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('write');

        $logger = new Logger($this->writer, 'test');
        $logger->log(LogSeverity::INFO, 'Test log message', ['key' => 'value']);
    }

    public function testGetLastEntries(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('readLast')->with(50);

        $logger = new Logger($this->writer, 'test');
        $logger->getLastEntries(50);
    }

    public function testGetEntriesBySeverity(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('readBySeverity')->with('ERROR', 50);

        $logger = new Logger($this->writer, 'test');
        $logger->getEntriesBySeverity('ERROR', 50);
    }

    public function testGetEntriesByCategory(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('readByCategory')->with('test', 50);

        $logger = new Logger($this->writer, 'test');
        $logger->getEntriesByCategory('test', 50);
    }

    public function testClearOldEntries(): void
    {
        $this->writer = $this->createMock(LogWriter::class);
        $this->writer->expects($this->once())->method('clearOld')->with(30);

        $logger = new Logger($this->writer, 'test');
        $logger->clearOldEntries(30);
    }
}
