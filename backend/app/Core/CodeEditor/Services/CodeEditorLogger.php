<?php
// backend/app/Core/CodeEditor/Services/CodeEditorLogger.php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Logging\Models\LogEntry;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Developer\DeveloperMode;

class CodeEditorLogger
{
    private LoggerInterface $logger;
    private DeveloperMode $developerMode;
    private array $sessionContext = [];

    public function __construct(LoggerInterface $logger, DeveloperMode $developerMode)
    {
        $this->logger = $logger;
        $this->developerMode = $developerMode;
        
        // Inicializácia session kontextu
        $this->sessionContext = [
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
    }

    /**
     * Logovanie prístupu k súboru
     */
    public function logFileAccess(string $path, string $action, array $metadata = []): void
    {
        $entry = new LogEntry(
            LogSeverity::INFO,
            'code_editor',
            sprintf('File %s: %s', $action, $path)
        );

        $entry->setContext(array_merge([
            'action' => $action,
            'path' => $path,
            'timestamp' => date('Y-m-d H:i:s'),
            'session' => $this->sessionContext
        ], $metadata));

        $this->logger->info($entry->getMessage(), $entry->getContext());
        
        // Debug log pre developer mode
        if ($this->developerMode->isActive()) {
            $this->developerMode->logQuery('FILE_ACCESS', [
                'action' => $action,
                'path' => $path,
                'metadata' => $metadata
            ]);
        }
    }

    /**
     * Logovanie zmien v súbore
     */
    public function logFileChange(string $path, string $action, array $changes, array $metadata = []): void
    {
        $severity = $this->determineSeverity($action, $changes);
        
        $entry = new LogEntry(
            $severity,
            'code_editor_changes',
            sprintf('File changed: %s (%s)', $path, $action)
        );

        $entry->setContext(array_merge([
            'action' => $action,
            'path' => $path,
            'changes' => $changes,
            'change_summary' => $this->summarizeChanges($changes),
            'timestamp' => date('Y-m-d H:i:s'),
            'session' => $this->sessionContext
        ], $metadata));

        $this->logger->log($severity, $entry->getMessage(), $entry->getContext());

        // Uloženie do developer debug logu
        if ($this->developerMode->isActive()) {
            $this->developerMode->logQuery('FILE_CHANGE', [
                'action' => $action,
                'path' => $path,
                'changes' => $changes,
                'summary' => $this->summarizeChanges($changes)
            ]);
        }
    }

    /**
     * Logovanie chýb
     */
    public function logError(string $path, \Throwable $error, array $metadata = []): void
    {
        $entry = new LogEntry(
            LogSeverity::ERROR,
            'code_editor_error',
            sprintf('Error in file %s: %s', $path, $error->getMessage())
        );

        $entry->setContext(array_merge([
            'path' => $path,
            'error' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
            'session' => $this->sessionContext
        ], $metadata));

        $this->logger->error($entry->getMessage(), $entry->getContext());

        if ($this->developerMode->isActive()) {
            $this->developerMode->logError($error);
        }
    }

    /**
     * Logovanie verzií
     */
    public function logVersion(string $path, int $version, string $action, array $metadata = []): void
    {
        $entry = new LogEntry(
            LogSeverity::INFO,
            'code_editor_version',
            sprintf('Version %d: %s - %s', $version, $action, $path)
        );

        $entry->setContext(array_merge([
            'action' => $action,
            'path' => $path,
            'version' => $version,
            'timestamp' => date('Y-m-d H:i:s'),
            'session' => $this->sessionContext
        ], $metadata));

        $this->logger->info($entry->getMessage(), $entry->getContext());
    }

    /**
     * Získanie logov pre konkrétny súbor
     */
    public function getFileLogs(string $path, int $limit = 100): array
    {
        $allLogs = $this->logger->getEntriesByCategory('code_editor', 1000);
        $fileLogs = array_filter($allLogs, function($log) use ($path) {
            return isset($log['context']['path']) && $log['context']['path'] === $path;
        });

        return array_slice($fileLogs, 0, $limit);
    }

    /**
     * Získanie štatistík logovania
     */
    public function getLogStats(): array
    {
        $allLogs = $this->logger->getLastEntries(1000);
        
        $stats = [
            'total' => 0,
            'by_action' => [],
            'by_severity' => [],
            'by_file' => [],
            'recent_activity' => []
        ];

        foreach ($allLogs as $log) {
            $stats['total']++;
            
            if (isset($log['context']['action'])) {
                $action = $log['context']['action'];
                $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;
            }
            
            if (isset($log['severity'])) {
                $stats['by_severity'][$log['severity']] = ($stats['by_severity'][$log['severity']] ?? 0) + 1;
            }
            
            if (isset($log['context']['path'])) {
                $path = $log['context']['path'];
                $stats['by_file'][$path] = ($stats['by_file'][$path] ?? 0) + 1;
            }
        }

        // Posledných 10 aktivít
        $stats['recent_activity'] = array_slice($allLogs, 0, 10);

        return $stats;
    }

    private function determineSeverity(string $action, array $changes): string
    {
        $dangerousActions = ['delete', 'overwrite', 'rename'];
        $criticalChanges = ['config', 'security', 'auth', 'database'];

        if (in_array($action, $dangerousActions)) {
            return LogSeverity::WARNING;
        }

        foreach ($criticalChanges as $critical) {
            if (strpos($action, $critical) !== false) {
                return LogSeverity::WARNING;
            }
        }

        return LogSeverity::INFO;
    }

    private function summarizeChanges(array $changes): string
    {
        $parts = [];
        
        if (isset($changes['added'])) {
            $parts[] = sprintf('%d added', count($changes['added']));
        }
        if (isset($changes['removed'])) {
            $parts[] = sprintf('%d removed', count($changes['removed']));
        }
        if (isset($changes['modified'])) {
            $parts[] = sprintf('%d modified', count($changes['modified']));
        }

        return empty($parts) ? 'No significant changes' : implode(', ', $parts);
    }
}
