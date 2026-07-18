<?php

declare(strict_types=1);

/**
 * Načíta .env z koreňa projektu a vráti DEV_UNLOCK_SECRET (rovnaká logika ako v services.php).
 */
function paginium_cli_bootstrap_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $envPath = dirname(__DIR__, 2);
    if (is_file($envPath . '/.env') && class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createUnsafeImmutable($envPath)->safeLoad();
    }

    $loaded = true;
}

function paginium_dev_unlock_secret(): string
{
    paginium_cli_bootstrap_env();

    $secret = (string) (getenv('DEV_UNLOCK_SECRET') ?: ($_ENV['DEV_UNLOCK_SECRET'] ?? ''));
    if ($secret !== '') {
        return $secret;
    }

    $appEnv = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'));
    $appDebug = filter_var(
        getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'true'),
        FILTER_VALIDATE_BOOLEAN
    );
    $localEnvs = ['testing', 'test', 'development', 'local'];

    if (in_array($appEnv, $localEnvs, true) || $appDebug) {
        return 'paginium-local-dev-unlock-secret';
    }

    return '';
}
