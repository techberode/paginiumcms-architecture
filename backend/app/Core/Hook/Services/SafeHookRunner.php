<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Hook\Services;

use PaginiumCMS\Core\Hook\PluginHookListener;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Http\Extensions\Models\PluginRecord;
use PaginiumCMS\Http\Extensions\Services\PluginHealthStore;
use PaginiumCMS\Http\Extensions\Services\PluginRegistry;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;
use Throwable;

/**
 * Isolates hook failures so one plugin cannot break the request (It.89c).
 *
 * Plugin listeners that exceed the error threshold are auto-disabled in the registry.
 */
final class SafeHookRunner
{
    public const DEFAULT_TIME_BUDGET_MS = 1000;

    public const DEFAULT_MEMORY_BUDGET_BYTES = 16_777_216;

    public const DEFAULT_FAILURE_THRESHOLD = 3;

    /** @var array<string, true> */
    private array $skipped = [];

    /** @var array<string, int> */
    private array $memoryFailures = [];

    public function __construct(
        private ?PluginRegistry $registry = null,
        private ?PluginHealthStore $health = null,
        private ?SecurityAuditStore $audit = null,
        private int $timeBudgetMs = self::DEFAULT_TIME_BUDGET_MS,
        private int $memoryBudgetBytes = self::DEFAULT_MEMORY_BUDGET_BYTES,
        private int $failureThreshold = self::DEFAULT_FAILURE_THRESHOLD,
    ) {
        $this->timeBudgetMs = max(0, $this->timeBudgetMs);
        $this->memoryBudgetBytes = max(0, $this->memoryBudgetBytes);
        $this->failureThreshold = max(1, $this->failureThreshold);
    }

    public function wrapPlugin(string $pluginId, callable $handler): PluginHookListener
    {
        return new PluginHookListener($pluginId, $handler);
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function run(string $hook, callable $callback, array $args): mixed
    {
        $pluginId = $callback instanceof PluginHookListener ? $callback->pluginId : null;

        if ($pluginId !== null && $this->shouldSkip($pluginId)) {
            return null;
        }

        $started = hrtime(true);
        $memStart = memory_get_usage(true);

        try {
            $result = call_user_func_array($callback, $args);
        } catch (Throwable $exception) {
            if ($pluginId !== null) {
                $this->failPlugin(
                    $pluginId,
                    $hook,
                    $this->shortClass($exception),
                    $exception instanceof \Error
                );
            }

            return null;
        }

        if ($pluginId !== null) {
            $elapsedMs = (int) ((hrtime(true) - $started) / 1_000_000);
            $memDelta = memory_get_usage(true) - $memStart;
            if ($elapsedMs >= $this->timeBudgetMs || $memDelta > $this->memoryBudgetBytes) {
                $this->failPlugin($pluginId, $hook, 'quota', false);

                return $result;
            }

            $this->health?->recordSuccess($pluginId);
            unset($this->memoryFailures[$pluginId]);
        }

        return $result;
    }

    private function shouldSkip(string $pluginId): bool
    {
        if (isset($this->skipped[$pluginId])) {
            return true;
        }

        $record = $this->registry?->get($pluginId);

        return $record !== null && $record->enabled === false;
    }

    private function failPlugin(string $pluginId, string $hook, string $error, bool $immediate): void
    {
        $plugin = LogSanitizer::value($pluginId, 64);
        $hookName = LogSanitizer::value($hook, 80);
        $reason = LogSanitizer::value($error, 120);

        $count = $this->health !== null
            ? $this->health->recordFailure($plugin, $hookName, $reason)
            : $this->bumpMemoryFailure($plugin);

        $this->audit?->append(
            'plugin_hook_failed',
            LogSeverity::ERROR,
            'plugin ' . $plugin . ' hook ' . $hookName . ' failed',
            null,
            null,
            null,
            [
                'pluginId' => $plugin,
                'hook' => $hookName,
                'reason' => $reason,
                'consecutiveFailures' => (string) $count,
            ]
        );

        if ($immediate || $count >= $this->failureThreshold) {
            $this->autoDisable($plugin, $hookName, $reason);
        }
    }

    private function autoDisable(string $pluginId, string $hook, string $reason): void
    {
        $this->skipped[$pluginId] = true;
        $this->health?->markAutoDisabled($pluginId);

        $registry = $this->registry;
        $existing = $registry?->get($pluginId);
        if ($registry !== null && $existing !== null && $existing->enabled) {
            $registry->upsert(new PluginRecord($pluginId, false, $existing->installedAt));
        }

        $this->audit?->append(
            'plugin_auto_disabled',
            LogSeverity::WARNING,
            'plugin ' . $pluginId . ' auto-disabled',
            null,
            null,
            null,
            [
                'pluginId' => $pluginId,
                'hook' => $hook,
                'reason' => $reason,
            ]
        );
    }

    private function bumpMemoryFailure(string $pluginId): int
    {
        $this->memoryFailures[$pluginId] = ($this->memoryFailures[$pluginId] ?? 0) + 1;

        return $this->memoryFailures[$pluginId];
    }

    private function shortClass(object $exception): string
    {
        $class = $exception::class;
        $pos = strrpos($class, '\\');

        return $pos === false ? $class : substr($class, $pos + 1);
    }
}
