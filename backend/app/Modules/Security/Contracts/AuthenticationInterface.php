<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Contracts;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\AuthenticationException;

/**
 * Rozhranie pre správu autentifikácie používateľov.
 */
interface AuthenticationInterface
{
    /**
     * Prihlási používateľa.
     *
     * @param string $email Email používateľa.
     * @param string $password Heslo.
     * @return User Prihlásený používateľ.
     * @throws AuthenticationException Ak prihlásenie zlyhá.
     */
    public function login(string $email, string $password): User;

    /**
     * Odhlási používateľa.
     */
    public function logout(): void;

    /**
     * Získa aktuálne prihláseného používateľa.
     *
     * @return User|null Prihlásený používateľ alebo null.
     */
    public function getCurrentUser(): ?User;

    /**
     * Overí, či je používateľ prihlásený.
     *
     * @return bool TRUE ak je prihlásený.
     */
    public function isAuthenticated(): bool;

    /**
     * Zmení heslo používateľa.
     *
     * @param User $user Používateľ.
     * @param string $oldPassword Staré heslo.
     * @param string $newPassword Nové heslo.
     * @throws AuthenticationException Ak staré heslo nie je správne.
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): void;

    /**
     * Resetuje heslo používateľa.
     *
     * @param string $email Email používateľa.
     * @return string Resetovací token.
     */
    public function resetPassword(string $email): string;

    /**
     * Overí resetovací token a nastaví nové heslo.
     *
     * @param string $token Resetovací token.
     * @param string $newPassword Nové heslo.
     * @throws AuthenticationException Ak token nie je platný.
     */
    public function verifyResetToken(string $token, string $newPassword): void;
}
