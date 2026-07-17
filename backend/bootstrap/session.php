<?php

declare(strict_types=1);

/**
 * Konfigurácia PHP session pre produkčné prostredie.
 */

// Importujeme reálne triedy z Security modulu
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Models\User;

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

// ---------- ZÁKLADNÉ NASTAVENIA ----------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', paginium_request_is_https() ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');

    // ---------- ŽIVOTNOSŤ SESSION ----------
    ini_set('session.gc_maxlifetime', '1440'); // 24 minút
    ini_set('session.cookie_lifetime', '1440');
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

    // session.sid_* je od PHP 8.5 deprecated – nové PHP má bezpečné defaulty.
    if (PHP_VERSION_ID < 80500) {
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
    }
}

/**
 * Bezpečnostný wrapper pre SessionManager chrániaci pred Session Hijacking.
 */
class SecureSessionManager extends SessionManager
{
    private const IP_KEY = '_session_ip';
    private const UA_KEY = '_session_user_agent';
    private const CREATED_KEY = '_session_created';

    public function __construct()
    {
        // Spustí konštruktor predka (ktorý pravdepodobne volá session_start)
        parent::__construct();

        // Ak metóda predka v PHPStane hlási chybu, uistite sa, že základný SessionManager ju reálne má.
        // Ak základný SessionManager používa iný názvov overenia (napr. isLogged), prepíšte to tu.
        if ($this->isAuthenticated()) {
            $this->validateSession();
        }
    }

    public function setUser(User $user): void
    {
        parent::setUser($user);

        // Uloženie bezpečnostných údajov pre kontrolu identity ottlačku prehliadača
        $_SESSION[self::IP_KEY] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION[self::UA_KEY] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $_SESSION[self::CREATED_KEY] = time();

        // Bezpečná regenerácia ID relácie proti Session Fixation útokom
        $this->regenerate();
    }

    private function validateSession(): void
    {
        // 1. Kontrola IP adresy
        $storedIp = $_SESSION[self::IP_KEY] ?? null;
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if ($storedIp !== null && $storedIp !== $currentIp) {
            $this->forceDestroy();
            throw new \RuntimeException('Session IP mismatch');
        }

        // 2. Kontrola User-Agent (Odtlačok prehliadača)
        $storedUa = $_SESSION[self::UA_KEY] ?? null;
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        if ($storedUa !== null && $storedUa !== $currentUa) {
            $this->forceDestroy();
            throw new \RuntimeException('Session User-Agent mismatch');
        }

        // 3. Kontrola absolútnej expirácie (max 24 hodín)
        $created = $_SESSION[self::CREATED_KEY] ?? null;
        if ($created !== null && (time() - $created) > 86400) {
            $this->forceDestroy();
            throw new \RuntimeException('Session expired');
        }
    }

    /**
     * Bezpečné zničenie relácie, ak zlyhá overenie integrity.
     */
    private function forceDestroy(): void
    {
        $this->destroy();
    }
}
