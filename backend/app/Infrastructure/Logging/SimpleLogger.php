<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use PaginiumCMS\Support\JsonHelper;
use Psr\Log\LoggerInterface;
use Stringable;

class SimpleLogger implements LoggerInterface
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = rtrim($logPath, '/');
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * @param array<int|string, mixed> $context
     */
    private function writeLog(string $level, string|Stringable $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context !== [] ? ' ' . JsonHelper::encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
        file_put_contents($this->logPath . '/app.log', $logMessage, FILE_APPEND);
    }

    /** @param array<int|string, mixed> $context */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('EMERGENCY', $message, $context);
    }

    /** @param array<int|string, mixed> $context */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('ALERT', $message, $context);
    }

    /** @param array<int|string, mixed> $context */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('CRITICAL', $message, $context);
    }

    /** @param array<int|string, mixed> $context */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('ERROR', $message, $context);
    }

    /** @param array<int|string, mixed> $context */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('WARNING', $message, $context);
    }

    /** @param array<int|string, mixed> $context */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('NOTICE', $message, $context);
    }

    /** @param array<int|string, mixed> $context */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('INFO', $message, $context);
    }

    /** @param array<int|string, mixed> $context */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->writeLog('DEBUG', $message, $context);
    }

    /**
     * @param mixed $level
     * @param array<int|string, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->writeLog((string) $level, $message, $context);
    }
}
