<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging;

use PaginiumCMS\Core\Logging\Services\DebugEventLogger;
use PHPUnit\Framework\TestCase;

final class DebugEventLoggerTest extends TestCase
{
    private string $logDir;
    private string $previousDebug;
    private string $previousEnv;

    protected function setUp(): void
    {
        $this->logDir = sys_get_temp_dir() . '/paginium-debug-test-' . uniqid('', true);
        mkdir($this->logDir, 0755, true);
        $this->previousDebug = (string) (getenv('APP_DEBUG') ?: '');
        $this->previousEnv = (string) (getenv('APP_ENV') ?: '');
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';
    }

    protected function tearDown(): void
    {
        if ($this->previousDebug !== '') {
            putenv('APP_DEBUG=' . $this->previousDebug);
            $_ENV['APP_DEBUG'] = $this->previousDebug;
        } else {
            putenv('APP_DEBUG');
            unset($_ENV['APP_DEBUG']);
        }

        if ($this->previousEnv !== '') {
            putenv('APP_ENV=' . $this->previousEnv);
            $_ENV['APP_ENV'] = $this->previousEnv;
        } else {
            putenv('APP_ENV');
            unset($_ENV['APP_ENV']);
        }

        $files = glob($this->logDir . '/*') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->logDir);
    }

    public function testDoesNotLogWhenDebugDisabled(): void
    {
        putenv('APP_DEBUG=false');
        $_ENV['APP_DEBUG'] = 'false';

        $ref = new \ReflectionClass(DebugEventLogger::class);
        $prop = $ref->getProperty('logDir');
        $prop->setValue(null, $this->logDir);

        DebugEventLogger::log('backend', 'test.event');

        $this->assertSame([], glob($this->logDir . '/*.log') ?: []);
    }

    public function testLogsWhenDebugEnabled(): void
    {
        putenv('APP_DEBUG=true');
        $_ENV['APP_DEBUG'] = 'true';

        $ref = new \ReflectionClass(DebugEventLogger::class);
        $prop = $ref->getProperty('logDir');
        $prop->setValue(null, $this->logDir);

        DebugEventLogger::log('backend', 'backend.startup', ['php' => PHP_VERSION]);

        $files = glob($this->logDir . '/*.log') ?: [];
        $this->assertCount(1, $files);
        $line = (string) file_get_contents($files[0]);
        $this->assertStringContainsString('backend.startup', $line);
    }

    public function testDoesNotLogDuringPHPUnit(): void
    {
        putenv('APP_DEBUG=true');
        putenv('APP_ENV=testing');
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_ENV'] = 'testing';

        $ref = new \ReflectionClass(DebugEventLogger::class);
        $prop = $ref->getProperty('logDir');
        $prop->setValue(null, $this->logDir);

        DebugEventLogger::log('backend', 'test.event');

        $this->assertSame([], glob($this->logDir . '/*.log') ?: []);
    }
}
