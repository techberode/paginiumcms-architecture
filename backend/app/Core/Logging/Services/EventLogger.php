<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;

/**
 * Špecializovaný logger pre systémové udalosti.
 */
class EventLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Zaloguje systémovú udalosť.
     */
    public function log(
        string $event,
        array $details = [],
        string $severity = 'INFO'
    ): void {
        $context = [
            'event' => $event,
            'details' => $details,
        ];

        $this->logger->log($severity, 'EVENT: ' . $event, $context);
    }

    /**
     * Zaloguje spustenie systému.
     */
    public function systemStart(): void
    {
        $this->log('system_start', ['version' => '2.0'], 'INFO');
    }

    /**
     * Zaloguje vypnutie systému.
     */
    public function systemShutdown(): void
    {
        $this->log('system_shutdown', [], 'INFO');
    }

    /**
     * Zaloguje chybu systému.
     */
    public function systemError(string $error, array $details = []): void
    {
        $this->log('system_error', array_merge(['error' => $error], $details), 'ERROR');
    }

    /**
     * Zaloguje varovanie systému.
     */
    public function systemWarning(string $warning, array $details = []): void
    {
        $this->log('system_warning', array_merge(['warning' => $warning], $details), 'WARNING');
    }

    /**
     * Zaloguje zálohovanie.
     */
    public function backup(string $type, bool $success, array $details = []): void
    {
        $severity = $success ? 'INFO' : 'ERROR';
        $this->log('backup_' . $type, array_merge(['success' => $success], $details), $severity);
    }

    /**
     * Zaloguje aktualizáciu.
     */
    public function update(string $component, string $from, string $to, bool $success): void
    {
        $severity = $success ? 'INFO' : 'ERROR';
        $this->log(
            'update',
            ['component' => $component, 'from' => $from, 'to' => $to, 'success' => $success],
            $severity
        );
    }

    /**
     * Zaloguje údržbu.
     */
    public function maintenance(string $action, array $details = []): void
    {
        $this->log('maintenance_' . $action, $details, 'INFO');
    }

    /**
     * Zaloguje detekciu útoku.
     */
    public function attackDetected(string $type, string $ip, array $details = []): void
    {
        $this->log(
            'attack_detected',
            array_merge(['type' => $type, 'ip' => $ip], $details),
            'CRITICAL'
        );
    }
}
