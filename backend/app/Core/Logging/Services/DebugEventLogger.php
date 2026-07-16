<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

/**
 * Debug event logger – zapíše udalosti pri štarte BE/FE do storage/logs/debug/.
 * Aktívny len keď APP_DEBUG=true (produkcia = ticho).
 */
final class DebugEventLogger
{
    private static ?string $logDir = null;

    public static function isEnabled(): bool
    {
        return filter_var(
            getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @param array<int|string, mixed> $context
 */public static function log(string $source, string $event, array $context = []): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $dir = self::logDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $entry = [
            'timestamp' => date('c'),
            'source' => $source,
            'event' => $event,
            'context' => $context,
        ];

        $file = $dir . '/' . date('Y-m-d') . '.log';
        file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }

    private static function logDir(): string
    {
        if (self::$logDir === null) {
            self::$logDir = dirname(__DIR__, 4) . '/storage/logs/debug';
        }

        return self::$logDir;
    }
}
