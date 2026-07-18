<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\User;

/**
 * Správca session.
 */
class SessionManager
{
    private const SESSION_KEY = 'paginium_user';
    private const TOTP_VERIFIED_KEY = 'paginium_totp_verified';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Uloží používateľa do session.
     *
     * @param User $user Používateľ.
     */
    public function setUser(User $user): void
    {
        $_SESSION[self::SESSION_KEY] = serialize($user);
        $this->regenerate();
    }

    /**
     * Aktualizuje serializovaného používateľa bez regenerácie session ID.
     */
    public function updateUser(User $user): void
    {
        $_SESSION[self::SESSION_KEY] = serialize($user);
    }

    /**
     * Získa používateľa zo session.
     *
     * @return User|null Prihlásený používateľ alebo null.
     */
    public function getUser(): ?User
    {
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
        unset($_SESSION[self::SESSION_KEY]);
        unset($_SESSION[self::TOTP_VERIFIED_KEY]);
        $this->regenerate();
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
        return isset($_SESSION[self::TOTP_VERIFIED_KEY]) && $_SESSION[self::TOTP_VERIFIED_KEY] === true;
    }

    /**
     * Nastaví TOTP ako overený.
     */
    public function setTotpVerified(): void
    {
        $_SESSION[self::TOTP_VERIFIED_KEY] = true;
    }

    /**
     * Zruší TOTP overenie.
     */
    public function clearTotpVerified(): void
    {
        unset($_SESSION[self::TOTP_VERIFIED_KEY]);
    }

    /**
     * Regeneruje ID session.
     */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Zničí session.
     */
    public function destroy(): void
    {
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
        $_SESSION[$key] = $value;
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
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Vymaže hodnotu zo session.
     *
     * @param string $key Kľúč.
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
