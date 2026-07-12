<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Developer;

use PaginiumCMS\Core\Config\ConfigManager;
use PaginiumCMS\Core\Event\EventDispatcher;

class DeveloperMode
{
    private ConfigManager $config;
    private EventDispatcher $events;
    private array $timers = [];
    private array $queries = [];
    private array $logs = [];

    public function __construct(ConfigManager $config, EventDispatcher $events)
    {
        $this->config = $config;
        $this->events = $events;
    }

    public static function isEnabled(): bool
    {
        return getenv('DEVELOPER_MODE') === 'true' || getenv('APP_DEBUG') === 'true';
    }

    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory' => memory_get_usage()
        ];
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0;
        }

        $duration = microtime(true) - $this->timers[$name]['start'];
        $memory = memory_get_usage() - $this->timers[$name]['memory'];

        $this->logs[] = [
            'type' => 'performance',
            'name' => $name,
            'duration' => round($duration * 1000, 2) . 'ms',
            // 'memory' => $this->formatBytes($memory)public function startTimer(
        ];

        unset($this->timers[$name]);
        return $duration;
    }

    public function logQuery(string $sql, array $params = [], float $duration = 0): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => round($duration * 1000, 2) . 'ms',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function logError(\Throwable $e): void
    {
        $this->logs[] = [
            'type' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function getDebugData(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'queries' => $this->queries,
            'logs' => $this->logs,
            'timers' => $this->timers,
            'server' => $_SERVER,
            'session' => $_SESSION ?? [],
        ];
    }

    public function renderDebugBar(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $data = $this->getDebugData();

        // Výpočty a ternárne operácie musíme urobiť PRED reťazcom
        $queriesCount = (isset($data['queries']) && is_array($data['queries'])) ? count($data['queries']) : 0;
        $errorsCount = $this->countErrors($data['logs'] ?? []);
        $formattedData = $this->formatDebugData($data);

        $html = <<<HTML
        <div id="paginium-debugbar" style="
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #1a1a2e;
        color: #eee;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        z-index: 999999;
        border-top: 2px solid #e94560;
        padding: 8px 16px;
        max-height: 200px;
        overflow-y: auto;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        ">
        <div style="display: flex; gap: 20px; flex-wrap: wrap; width: 100%;">
        <span style="color: #e94560;">🐞 DEBUG MODE</span>
        <span>PHP: <strong>{$data['php_version']}</strong></span>
        <span>Memory: <strong>{$data['memory_usage']}</strong></span>
        <span>Peak: <strong>{$data['peak_memory']}</strong></span>
        <span>Queries: <strong>{$queriesCount}</strong></span>
        <span>Errors: <strong>{$errorsCount}</strong></span>
        <button onclick="document.getElementById('paginium-debug-details').style.display = 'block'"
        style="background: #e94560; border: none; color: white; padding: 2px 12px; border-radius: 4px; cursor: pointer;">
        Details
        </button>
        </div>
        <div id="paginium-debug-details" style="display: none; width: 100%; max-height: 300px; overflow-y: auto; background: #16213e; padding: 12px; border-radius: 4px; margin-top: 8px;">
        <pre style="margin: 0; color: #eee; font-size: 11px; white-space: pre-wrap;">{$formattedData}</pre>
        </div>
        </div>
        HTML; // TENTO RIADOK MUSÍ BYŤ NA ÚPLNOM ZAČIATKU BEZ MEDZIER!

        return $html;
    }


    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function countErrors(array $logs): int
    {
        return count(array_filter($logs, fn($log) => $log['type'] === 'error'));
    }

    private function formatDebugData(array $data): string
    {
        $output = "=== PERFORMANCE ===\n";
        foreach ($data['timers'] as $name => $timer) {
            $output .= "  - $name: running...\n";
        }
        foreach ($data['logs'] as $log) {
            if ($log['type'] === 'performance') {
                $output .= "  - {$log['name']}: {$log['duration']} ({$log['memory']})\n";
            }
        }

        $output .= "\n=== QUERIES ===\n";
        foreach ($data['queries'] as $i => $query) {
            $output .= sprintf("  %d. %s [%s]\n", $i + 1, $query['sql'], $query['duration']);
            if ($query['params']) {
                $output .= "     Params: " . json_encode($query['params']) . "\n";
            }
        }

        $output .= "\n=== ERRORS ===\n";
        foreach ($data['logs'] as $log) {
            if ($log['type'] === 'error') {
                $output .= "  - {$log['message']} in {$log['file']}:{$log['line']}\n";
            }
        }

        return $output;
    }
}
