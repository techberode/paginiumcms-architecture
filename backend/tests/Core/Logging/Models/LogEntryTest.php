<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging\Models;

use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

class LogEntryTest extends TestCase
{
    public function testCreateEntry(): void
    {
        $entry = new LogEntry(
            LogSeverity::INFO,
            'test',
            'Test message'
        );

        $this->assertNotEmpty($entry->getId());
        $this->assertNotEmpty($entry->getTimestamp());
        $this->assertEquals(LogSeverity::INFO, $entry->getSeverity());
        $this->assertEquals('test', $entry->getCategory());
        $this->assertEquals('Test message', $entry->getMessage());
    }

    public function testSetUserId(): void
    {
        $entry = new LogEntry(LogSeverity::INFO, 'test', 'Test');
        $entry->setUserId('user_123');

        $this->assertEquals('user_123', $entry->getUserId());
    }

    public function testSetIp(): void
    {
        $entry = new LogEntry(LogSeverity::INFO, 'test', 'Test');
        $entry->setIp('127.0.0.1');

        $this->assertEquals('127.0.0.1', $entry->getIp());
    }

    public function testSetContext(): void
    {
        $entry = new LogEntry(LogSeverity::INFO, 'test', 'Test');
        $context = ['key' => 'value', 'number' => 123];
        $entry->setContext($context);

        $this->assertEquals($context, $entry->getContext());
    }

    public function testSetFileAndLine(): void
    {
        $entry = new LogEntry(LogSeverity::INFO, 'test', 'Test');
        $entry->setFile('test.php');
        $entry->setLine(42);

        $this->assertEquals('test.php', $entry->getFile());
        $this->assertEquals(42, $entry->getLine());
    }

    public function testSeverityChecks(): void
    {
        $info = new LogEntry(LogSeverity::INFO, 'test', 'Info');
        $warning = new LogEntry(LogSeverity::WARNING, 'test', 'Warning');
        $error = new LogEntry(LogSeverity::ERROR, 'test', 'Error');
        $critical = new LogEntry(LogSeverity::CRITICAL, 'test', 'Critical');
        $debug = new LogEntry(LogSeverity::DEBUG, 'test', 'Debug');

        $this->assertTrue($info->isInfo());
        $this->assertFalse($info->isWarning());
        $this->assertFalse($info->isError());

        $this->assertTrue($warning->isWarning());
        $this->assertFalse($warning->isInfo());

        $this->assertTrue($error->isError());
        $this->assertFalse($error->isInfo());

        $this->assertTrue($critical->isCritical());
        $this->assertTrue($debug->isDebug());
    }

    public function testToArray(): void
    {
        $entry = new LogEntry(LogSeverity::INFO, 'test', 'Test message');
        $entry->setUserId('user_123');
        $entry->setIp('127.0.0.1');
        $entry->setContext(['key' => 'value']);

        $data = $entry->toArray();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('severity', $data);
        $this->assertArrayHasKey('category', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('userId', $data);
        $this->assertArrayHasKey('ip', $data);
        $this->assertArrayHasKey('context', $data);
        $this->assertEquals('user_123', $data['userId']);
        $this->assertEquals('127.0.0.1', $data['ip']);
        $this->assertEquals(['key' => 'value'], $data['context']);
    }

    public function testJsonSerialize(): void
    {
        $entry = new LogEntry(LogSeverity::INFO, 'test', 'Test message');
        $json = json_encode($entry);
        self::assertIsString($json);

        $this->assertJson($json);
        $data = JsonHelper::decode($json);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('severity', $data);
    }

    public function testThrowsExceptionOnInvalidSeverity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LogEntry('INVALID', 'test', 'Test');
    }
}
