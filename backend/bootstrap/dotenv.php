<?php

declare(strict_types=1);

/**
 * Load .env for web and CLI. When the process already has APP_KEY="" (common in
 * Docker Compose placeholders), Dotenv safeLoad will not replace it — secrets
 * cannot decrypt in cron/scheduler while PHP-FPM may still work from file-based env.
 */
function paginium_bootstrap_dotenv(string $projectRoot, string $backendRoot, string $appEnv): void
{
    if ($appEnv === 'testing' || !class_exists(\Dotenv\Dotenv::class)) {
        return;
    }

    $dir = null;
    if (is_file($projectRoot . '/.env')) {
        $dir = $projectRoot;
    } elseif (is_file($backendRoot . '/.env')) {
        $dir = $backendRoot;
    }

    if ($dir === null) {
        return;
    }

    \Dotenv\Dotenv::createUnsafeImmutable($dir)->safeLoad();

    $appKey = trim((string) (getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? '') ?: ($_SERVER['APP_KEY'] ?? '')));
    if ($appKey !== '') {
        return;
    }

    \Dotenv\Dotenv::createUnsafeMutable($dir)->load();
}
