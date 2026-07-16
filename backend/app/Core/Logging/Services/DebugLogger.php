<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Logging\Models\LogSeverity;

class DebugLogger
{
    private LoggerInterface $logger;
    private bool $enabled;
    private string $level;
    private bool $showInUi;
    private bool $logToFile;

    /**
     * @param array<int|string, mixed> $config
     */
    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->enabled = $config['enabled'] ?? false;
        $this->level = $config['level'] ?? 'INFO';
        $this->showInUi = $config['show_in_ui'] ?? false;
        $this->logToFile = $config['log_to_file'] ?? true;
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function debug(string $message, array $context = [], string $severity = 'DEBUG'): void
    {
        if (!$this->enabled) {
            return;
        }

        if (LogSeverity::getLevel($severity) > LogSeverity::getLevel($this->level)) {
            return;
        }

        if ($this->logToFile) {
            $this->logger->debug($message, $context);
        }

        if ($this->showInUi) {
            $this->triggerUiNotification($message, $context, $severity);
        }
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->debug($message, $context, 'INFO');
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->debug($message, $context, 'WARNING');
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->debug($message, $context, 'ERROR');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): void
    {
        if (LogSeverity::isValid($level)) {
            $this->level = $level;
        }
    }

    public function isShowInUi(): bool
    {
        return $this->showInUi;
    }

    public function setShowInUi(bool $show): void
    {
        $this->showInUi = $show;
    }

    /**
     * @param array<int|string, mixed> $context
     */
    private function triggerUiNotification(string $message, array $context, string $severity): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $notifications = $_SESSION['_debug_notifications'] ?? [];
        $notifications[] = [
            'message' => $message,
            'context' => $context,
            'severity' => $severity,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (count($notifications) > 100) {
            $notifications = array_slice($notifications, -100);
        }

        $_SESSION['_debug_notifications'] = $notifications;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getUiNotifications(): array
    {
        return $_SESSION['_debug_notifications'] ?? [];
    }

    public function clearUiNotifications(): void
    {
        unset($_SESSION['_debug_notifications']);
    }
}
