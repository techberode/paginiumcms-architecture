<?php

namespace App\Infrastructure\Logging;

class SimpleLogger implements \Psr\Log\LoggerInterface
{
    private string $logPath;
    
    public function __construct(string $logPath)
    {
        $this->logPath = rtrim($logPath, '/');
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    private function writeLog(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
        file_put_contents($this->logPath . '/app.log', $logMessage, FILE_APPEND);
    }
    
    public function emergency($message, array $context = []): void { $this->writeLog('EMERGENCY', $message, $context); }
    public function alert($message, array $context = []): void { $this->writeLog('ALERT', $message, $context); }
    public function critical($message, array $context = []): void { $this->writeLog('CRITICAL', $message, $context); }
    public function error($message, array $context = []): void { $this->writeLog('ERROR', $message, $context); }
    public function warning($message, array $context = []): void { $this->writeLog('WARNING', $message, $context); }
    public function notice($message, array $context = []): void { $this->writeLog('NOTICE', $message, $context); }
    public function info($message, array $context = []): void { $this->writeLog('INFO', $message, $context); }
    public function debug($message, array $context = []): void { $this->writeLog('DEBUG', $message, $context); }
    public function log($level, $message, array $context = []): void { $this->writeLog((string) $level, (string) $message, $context); }
}
