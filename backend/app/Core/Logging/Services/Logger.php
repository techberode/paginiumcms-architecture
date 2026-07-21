<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\Logging\Models\LogSeverity;

/**
 * Hlavný logger.
 */
class Logger implements LoggerInterface
{
    private LogWriterInterface $writer;
    private string $category;

    public function __construct(LogWriterInterface $writer, string $category = 'app')
    {
        $this->writer = $writer;
        $this->category = $category;
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LogSeverity::INFO, $message, $context);
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LogSeverity::WARNING, $message, $context);
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LogSeverity::ERROR, $message, $context);
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(LogSeverity::CRITICAL, $message, $context);
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogSeverity::DEBUG, $message, $context);
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function log(string $severity, string $message, array $context = []): void
    {
        $entry = new LogEntry($severity, $this->category, $message);

        // Pridanie kontextu
        if (!empty($context)) {
            $entry->setContext($context);
        }

        // Pridanie IP adresy
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $entry->setIp($_SERVER['REMOTE_ADDR']);
        }

        // Pridanie súboru a riadku (voliteľné)
        $this->addFileAndLine($entry);

        $this->writer->write($entry);
    }

    public function writeEntry(LogEntry $entry): void
    {
        if ($entry->getIp() === null && isset($_SERVER['REMOTE_ADDR'])) {
            $entry->setIp((string) $_SERVER['REMOTE_ADDR']);
        }

        $this->writer->write($entry);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getLastEntries(int $limit = 100): array
    {
        return $this->writer->readLast($limit);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getEntriesBySeverity(string $severity, int $limit = 100): array
    {
        return $this->writer->readBySeverity($severity, $limit);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getEntriesByCategory(string $category, int $limit = 100): array
    {
        return $this->writer->readByCategory($category, $limit);
    }

    public function clearOldEntries(int $days = 30): int
    {
        return $this->writer->clearOld($days);
    }

    /**
     * Pridá informácie o súbore a riadku (debug backtrace).
     */
    private function addFileAndLine(LogEntry $entry): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        foreach ($trace as $frame) {
            // Preskočíme interné volania
            if (isset($frame['class']) && strpos($frame['class'], 'PaginiumCMS\\Core\\Logging') === 0) {
                continue;
            }

            if (isset($frame['file'])) {
                $entry->setFile(basename($frame['file']));
                $entry->setLine($frame['line'] ?? 0);
                break;
            }
        }
    }
}
