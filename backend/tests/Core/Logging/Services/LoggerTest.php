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

        // APP_ENV=testing suppresses durable Logger writes (ISS-111).
        // These unit tests mock the writer and must exercise the write path.
        putenv('PAGINIUM_LOGGER_ALLOW_TESTING=1');
        $_ENV['PAGINIUM_LOGGER_ALLOW_TESTING'] = '1';
        $_SERVER['PAGINIUM_LOGGER_ALLOW_TESTING'] = '1';

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

    protected function tearDown(): void
    {
        putenv('PAGINIUM_LOGGER_ALLOW_TESTING');
        unset($_ENV['PAGINIUM_LOGGER_ALLOW_TESTING'], $_SERVER['PAGINIUM_LOGGER_ALLOW_TESTING']);
        parent::tearDown();
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

    public function testSkipsWritesInTestingWithoutAllowFlag(): void
    {
        putenv('PAGINIUM_LOGGER_ALLOW_TESTING');
        unset($_ENV['PAGINIUM_LOGGER_ALLOW_TESTING'], $_SERVER['PAGINIUM_LOGGER_ALLOW_TESTING']);

        $prevEnv = getenv('APP_ENV');
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';

        try {
            $this->writer = $this->createMock(LogWriter::class);
            $this->writer->expects($this->never())->method('write');

            $logger = new Logger($this->writer, 'test');
            $logger->info('must not reach disk in testing');
        } finally {
            if ($prevEnv === false) {
                putenv('APP_ENV');
                unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
            } else {
                putenv('APP_ENV=' . $prevEnv);
                $_ENV['APP_ENV'] = $prevEnv;
                $_SERVER['APP_ENV'] = $prevEnv;
            }
        }
    }
}
