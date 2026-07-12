<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Contracts;

use PaginiumCMS\Modules\Security\Exception\SecurityException;

/**
 * Rozhranie pre ochranu proti CSRF útokom.
 */
interface CsrfProtectionInterface
{
    /**
     * Generuje CSRF token.
     *
     * @param string $key Identifikátor (napr. názov formulára).
     * @return string CSRF token.
     */
    public function generateToken(string $key): string;

    /**
     * Overí CSRF token.
     *
     * @param string $key Identifikátor.
     * @param string $token Token na overenie.
     * @return bool TRUE ak je token platný.
     */
    public function verifyToken(string $key, string $token): bool;

    /**
     * Overí CSRF token – vyhodí výnimku ak je neplatný.
     *
     * @param string $key Identifikátor.
     * @param string $token Token na overenie.
     * @throws SecurityException Ak je token neplatný.
     */
    public function requireValidToken(string $key, string $token): void;

    /**
     * Získa CSRF token pre formulár.
     *
     * @param string $key Identifikátor.
     * @return string CSRF token.
     */
    public function getToken(string $key): string;

    /**
     * Vymaže CSRF token.
     *
     * @param string $key Identifikátor.
     */
    public function clearToken(string $key): void;
}
