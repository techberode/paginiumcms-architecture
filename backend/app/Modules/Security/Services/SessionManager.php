<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Správca session.
 */
class SessionManager
{
    private const SESSION_KEY = 'paginium_user';
    private const TOTP_VERIFIED_KEY = 'paginium_totp_verified';

    private bool $writeLockReleased = false;

    public function __construct(
        protected ?PerformanceContext $performance = null,
    ) {
    }

    /**
     * Drops the exclusive session file lock while keeping $_SESSION in memory.
     *
     * Safe after reads or once pending session writes for this request are done.
     */
    public function releaseWriteLock(): void
    {
        if ($this->writeLockReleased || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $this->performance?->recordSessionReleased();
        session_write_close();
        $this->writeLockReleased = true;
    }

    public function isWriteLockReleased(): bool
    {
        return $this->writeLockReleased;
    }

    protected function ensureSessionActive(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            $lockStarted = hrtime(true);
            session_start();
            $this->performance?->recordSessionLockDuration(hrtime(true) - $lockStarted);
            $this->performance?->markSessionActive();
            $this->writeLockReleased = false;
        }
    }

    /**
     * Uloží používateľa do session.
     *
     * @param User $user Používateľ.
     */
    public function setUser(User $user): void
    {
        $this->ensureSessionActive();
        $_SESSION[self::SESSION_KEY] = serialize($user);
        $this->regenerate();
        $this->releaseWriteLock();
    }

    /**
     * Aktualizuje serializovaného používateľa bez regenerácie session ID.
     */
    public function updateUser(User $user): void
    {
        $this->ensureSessionActive();
        $_SESSION[self::SESSION_KEY] = serialize($user);
        $this->releaseWriteLock();
    }

    /**
     * Získa používateľa zo session.
     *
     * @return User|null Prihlásený používateľ alebo null.
     */
    public function getUser(): ?User
    {
        $this->ensureSessionActive();

        if (!isset($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        try {
            return unserialize($_SESSION[self::SESSION_KEY], ['allowed_classes' => [User::class]]);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Odstráni používateľa zo session (odhlásenie).
     */
    public function clearUser(): void
    {
        $this->ensureSessionActive();
        unset($_SESSION[self::SESSION_KEY]);
        unset($_SESSION[self::TOTP_VERIFIED_KEY]);
        $this->regenerate();
        $this->releaseWriteLock();
    }

    /**
     * Zistí, či je používateľ prihlásený.
     *
     * @return bool TRUE ak je prihlásený.
     */
    public function isAuthenticated(): bool
    {
        return $this->getUser() !== null;
    }

    /**
     * Zistí, či je TOTP overený.
     *
     * @return bool TRUE ak je TOTP overený.
     */
    public function isTotpVerified(): bool
    {
        $this->ensureSessionActive();

        return isset($_SESSION[self::TOTP_VERIFIED_KEY]) && $_SESSION[self::TOTP_VERIFIED_KEY] === true;
    }

    /**
     * Nastaví TOTP ako overený.
     */
    public function setTotpVerified(): void
    {
        $this->ensureSessionActive();
        $_SESSION[self::TOTP_VERIFIED_KEY] = true;
        $this->releaseWriteLock();
    }

    /**
     * Zruší TOTP overenie.
     */
    public function clearTotpVerified(): void
    {
        $this->ensureSessionActive();
        unset($_SESSION[self::TOTP_VERIFIED_KEY]);
        $this->releaseWriteLock();
    }

    /**
     * Overí integritu session (no-op v základnej triede).
     */
    public function ensureValid(): bool
    {
        return true;
    }

    /**
     * Predĺži server-side session pri aktívnej práci (no-op v základnej triede).
     */
    public function touch(): void
    {
    }

    /**
     * Obnoví Max-Age session cookie v prehliadači (sliding expiration pri aktívnej práci).
     */
    public function refreshCookieLifetime(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        $sessionName = session_name();
        $sessionId = session_id();
        if ($sessionName === false || $sessionId === false) {
            return;
        }

        $lifetime = (new DemoMode())->sessionLifetimeSeconds();
        if ($lifetime <= 0) {
            return;
        }

        $params = session_get_cookie_params();
        $sameSite = 'Lax';
        if (in_array($params['samesite'], ['Strict', 'strict'], true)) {
            $sameSite = 'Strict';
        } elseif (in_array($params['samesite'], ['None', 'none'], true)) {
            $sameSite = 'None';
        }

        setcookie($sessionName, $sessionId, [
            'expires' => time() + $lifetime,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $sameSite,
        ]);
    }

    /**
     * Regeneruje ID session.
     */
    public function regenerate(): void
    {
        $this->ensureSessionActive();
        session_regenerate_id(true);
    }

    /**
     * Zničí session.
     */
    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        session_destroy();
    }

    /**
     * Uloží hodnotu do session.
     *
     * @param string $key Kľúč.
     * @param mixed $value Hodnota.
     */
    public function set(string $key, $value): void
    {
        $this->ensureSessionActive();
        $_SESSION[$key] = $value;
        $this->releaseWriteLock();
    }

    /**
     * Získa hodnotu zo session.
     *
     * @param string $key Kľúč.
     * @param mixed $default Predvolená hodnota.
     * @return mixed Hodnota.
     */
    public function get(string $key, $default = null)
    {
        $this->ensureSessionActive();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Vymaže hodnotu zo session.
     *
     * @param string $key Kľúč.
     */
    public function remove(string $key): void
    {
        $this->ensureSessionActive();
        unset($_SESSION[$key]);
        $this->releaseWriteLock();
    }
}
