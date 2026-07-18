<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Developer;

use PaginiumCMS\Core\Developer\Services\DeveloperLogger;
use PaginiumCMS\Core\Config\ConfigManager;
use PaginiumCMS\Core\Event\EventDispatcher;
use PaginiumCMS\Support\JsonHelper;

/**
 * Runtime kolektor pre Developer Mode (timery, query, chyby).
 *
 * Aktívny len ak DeveloperModeGate->isUnlocked() – inak len no-op,
 * aby produkčný beh nebol zaťažený zberom metrik.
 */
class DeveloperMode
{
    /** @var array<int|string, mixed> */
    private array $timers = [];
    /** @var array<int|string, mixed> */
    private array $queries = [];
    /** @var array<int|string, mixed> */
    private array $logs = [];

    public function __construct(
        private ConfigManager $config,
        private EventDispatcher $events,
        private DeveloperModeGate $gate,
        private DeveloperLogger $developerLogger
    ) {
    }

    public function isActive(): bool
    {
        if (!$this->gate->isUnlocked()) {
            return false;
        }

        $this->config->get('developer.enabled', true);
        return true;
    }

    /** @deprecated Použite isActive() – rešpektuje gate; static kontroluje len env flag. */
    public function isEnabled(): bool
    {
        return $this->isActive();
    }

    public static function isFeatureFlagEnabled(): bool
    {
        $developerMode = getenv('DEVELOPER_MODE') ?: ($_ENV['DEVELOPER_MODE'] ?? '');
        if ($developerMode === 'true' || filter_var($developerMode, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $appDebug = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? '');
        if ($appDebug === 'true' || filter_var($appDebug, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $appEnv = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'));

        return in_array($appEnv, ['testing', 'test', 'development', 'local'], true);
    }

    /** @deprecated Použite isFeatureFlagEnabled() */
    public static function isEnabledStatic(): bool
    {
        return self::isFeatureFlagEnabled();
    }

    public function startTimer(string $name): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->events->getListeners('developer.timer.start');
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory' => memory_get_usage(),
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
        if (!$this->isActive() || !isset($this->timers[$name])) {
            return 0;
        }

        $duration = microtime(true) - $this->timers[$name]['start'];
        $memory = memory_get_usage() - $this->timers[$name]['memory'];

        $entry = [
            'type' => 'performance',
            'name' => $name,
            'duration' => round($duration * 1000, 2) . 'ms',
            'memory' => $this->formatBytes((float) $memory),
        ];
        $this->logs[] = $entry;
        $this->developerLogger->log('performance', 'info', $name, $entry);

        unset($this->timers[$name]);

        return $duration;
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function logQuery(string $sql, array $params = [], float $duration = 0): void
    {
        if (!$this->isActive()) {
            return;
        }

        $entry = [
            'sql' => $sql,
            'params' => $params,
            'duration' => round($duration * 1000, 2) . 'ms',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $this->queries[] = $entry;
        $this->developerLogger->log('query', 'debug', $sql, $entry);
    }

    public function logError(\Throwable $e): void
    {
        if (!$this->isActive()) {
            return;
        }

        $entry = [
            'type' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $this->logs[] = $entry;
        $this->developerLogger->log('error', 'error', $e->getMessage(), $entry);
    }

    /**
     * @param array<int|string, mixed> $context
 */public function logEvent(string $channel, string $message, array $context = []): void
    {
        if (!$this->isActive()) {
            return;
        }

        $entry = [
            'type' => 'event',
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $this->logs[] = $entry;
        $this->developerLogger->log($channel, 'info', $message, $context);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getDebugData(): array
    {
        return [
            'active' => $this->isActive(),
            'gate' => $this->gate->getStatus(),
            'php_version' => PHP_VERSION,
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'queries' => $this->queries,
            'logs' => $this->logs,
            'timers' => $this->timers,
        ];
    }

    public function renderDebugBar(): string
    {
        if (!$this->isActive()) {
            return '';
        }

        $data = $this->getDebugData();
        $queriesCount = count($data['queries']);
        $errorsCount = $this->countErrors($data['logs']);
        $formattedData = $this->formatDebugData($data);

        return <<<HTML
        <div id="paginium-debugbar" style="position:fixed;bottom:0;left:0;right:0;background:#1a1a2e;color:#eee;font-family:monospace;font-size:12px;z-index:999999;border-top:2px solid #e94560;padding:8px 16px;">
        <span style="color:#e94560;">DEBUG</span>
        PHP: {$data['php_version']} | Memory: {$data['memory_usage']} | Queries: {$queriesCount} | Errors: {$errorsCount}
        <pre style="display:none" id="paginium-debug-details">{$formattedData}</pre>
        </div>
        HTML;
    }

    /**
     * @param array<int|string, mixed> $logs
     */
    private function countErrors(array $logs): int
    {
        return count(array_filter($logs, fn ($log) => ($log['type'] ?? '') === 'error'));
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function formatDebugData(array $data): string
    {
        return JsonHelper::encode($data, JSON_PRETTY_PRINT);
    }
}
