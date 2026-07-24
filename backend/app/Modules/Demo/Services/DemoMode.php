<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

/**
 * Central DEMO_MODE gate (Iteration 13).
 */
final class DemoMode
{
    public function isEnabled(): bool
    {
        return self::isEnabledFromEnv();
    }

    public static function isEnabledFromEnv(): bool
    {
        return filter_var(
            getenv('DEMO_MODE') ?: ($_ENV['DEMO_MODE'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Active flat-file root: demo tree when DEMO_MODE, otherwise production content.
     */
    public static function resolveContentBasePath(string $storageAppDir): string
    {
        $base = rtrim($storageAppDir, '/');

        return self::isEnabledFromEnv()
            ? $base . '/demo'
            : $base . '/content';
    }

    public function storageRelativePath(): string
    {
        return 'storage/app/demo';
    }

    public function autoResetMinutes(): int
    {
        $raw = getenv('DEMO_AUTO_RESET_MINUTES') ?: ($_ENV['DEMO_AUTO_RESET_MINUTES'] ?? 60);

        return max(0, (int) $raw);
    }

    public function publicDemoUrl(): string
    {
        $url = trim((string) (getenv('DEMO_PUBLIC_URL') ?: ($_ENV['DEMO_PUBLIC_URL'] ?? '')));

        return $url !== '' ? $url : 'https://demo.paginiumcms.com';
    }

    public function sessionLifetimeSeconds(): int
    {
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $nonDemoDefault = $appEnv === 'production' ? 7200 : 28800;
        $default = $this->isEnabled() ? 14400 : $nonDemoDefault;
        $raw = getenv('SESSION_LIFETIME') ?: ($_ENV['SESSION_LIFETIME'] ?? $default);

        return max(300, (int) $raw);
    }
}
