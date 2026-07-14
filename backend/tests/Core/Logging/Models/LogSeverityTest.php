<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging\Models;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PHPUnit\Framework\TestCase;

class LogSeverityTest extends TestCase
{
    public function testGetAll(): void
    {
        $all = LogSeverity::getAll();
        $this->assertContains('INFO', $all);
        $this->assertContains('WARNING', $all);
        $this->assertContains('ERROR', $all);
        $this->assertContains('CRITICAL', $all);
        $this->assertContains('DEBUG', $all);
    }

    public function testIsValid(): void
    {
        $this->assertTrue(LogSeverity::isValid('INFO'));
        $this->assertTrue(LogSeverity::isValid('WARNING'));
        $this->assertTrue(LogSeverity::isValid('ERROR'));
        $this->assertTrue(LogSeverity::isValid('CRITICAL'));
        $this->assertTrue(LogSeverity::isValid('DEBUG'));
        $this->assertFalse(LogSeverity::isValid('INVALID'));
    }

    public function testGetColor(): void
    {
        $this->assertEquals('blue', LogSeverity::getColor('INFO'));
        $this->assertEquals('yellow', LogSeverity::getColor('WARNING'));
        $this->assertEquals('red', LogSeverity::getColor('ERROR'));
        $this->assertEquals('magenta', LogSeverity::getColor('CRITICAL'));
        $this->assertEquals('gray', LogSeverity::getColor('DEBUG'));
    }

    public function testGetLevel(): void
    {
        $this->assertEquals(0, LogSeverity::getLevel('DEBUG'));
        $this->assertEquals(1, LogSeverity::getLevel('INFO'));
        $this->assertEquals(2, LogSeverity::getLevel('WARNING'));
        $this->assertEquals(3, LogSeverity::getLevel('ERROR'));
        $this->assertEquals(4, LogSeverity::getLevel('CRITICAL'));
        $this->assertEquals(0, LogSeverity::getLevel('INVALID'));
    }
}
