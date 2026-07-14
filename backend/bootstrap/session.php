<?php

declare(strict_types=1);

/**
 * Konfigurácia PHP session pre produkčné prostredie.
 */

// Importujeme reálne triedy z Security modulu
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Models\User;

// ---------- ZÁKLADNÉ NASTAVENIA ----------
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');  // IBA HTTPS!
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');

// ---------- ŽIVOTNOSŤ SESSION ----------
ini_set('session.gc_maxlifetime', '1440'); // 24 minút
ini_set('session.cookie_lifetime', '1440');
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');

// ---------- BEZPEČNOSŤ ----------
ini_set('session.sid_length', '48');
ini_set('session.sid_bits_per_character', '6');

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
        if (method_exists($this, 'isAuthenticated') && $this->isAuthenticated()) {
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
        if (method_exists($this, 'regenerate')) {
            $this->regenerate();
        } else {
            session_regenerate_id(true);
        }
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
        if (method_exists($this, 'destroy')) {
            $this->destroy();
        } else {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                          $params["path"], $params["domain"],
                          $params["secure"], $params["httponly"]
                );
            }
            @session_destroy();
        }
    }
}
