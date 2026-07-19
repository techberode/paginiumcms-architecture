<?php

declare(strict_types=1);

/**
 * Konfigurácia PHP session pre produkčné prostredie.
 */

use PaginiumCMS\Modules\Demo\Services\DemoMode;

/**
 * Secure flag len pri reálnom HTTPS (priamo alebo cez reverse proxy).
 * LAN test na http://192.168.x.x:8081 inak neuloží PHPSESSID do prehliadača.
 */
if (!function_exists('paginium_request_is_https')) {
    function paginium_request_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

        return $forwarded === 'https';
    }
}

if (!function_exists('paginium_session_lifetime_seconds')) {
    function paginium_session_lifetime_seconds(): int
    {
        if (class_exists(DemoMode::class)) {
            return (new DemoMode())->sessionLifetimeSeconds();
        }

        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $default = $appEnv === 'production' ? 7200 : 28800;

        $rawLifetime = getenv('SESSION_LIFETIME') ?: ($_ENV['SESSION_LIFETIME'] ?? null);
        if ($rawLifetime !== null && $rawLifetime !== '') {
            return max(300, (int) $rawLifetime);
        }

        return $default;
    }
}

if (!function_exists('paginium_session_use_strict_mode')) {
    /**
     * PHP session.use_strict_mode – odmietne neplatné/neinicializované session ID.
     * Nie je to to isté ako SESSION_STRICT (IP/UA binding v SecureSessionManager).
     */
    function paginium_session_use_strict_mode(): bool
    {
        $raw = getenv('SESSION_USE_STRICT_MODE') ?: ($_ENV['SESSION_USE_STRICT_MODE'] ?? null);
        if ($raw === null || $raw === '') {
            return true;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
}

// ---------- ZÁKLADNÉ NASTAVENIA ----------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', paginium_request_is_https() ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', paginium_session_use_strict_mode() ? '1' : '0');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_path', '/');

    $sessionLifetime = paginium_session_lifetime_seconds();

    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    ini_set('session.cookie_lifetime', (string) $sessionLifetime);
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

    if (PHP_VERSION_ID < 80500) {
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
    }
}
