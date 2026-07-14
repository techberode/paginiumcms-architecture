<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Contracts;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\TwoFactorException;

/**
 * Rozhranie pre správu dvojfaktorovej autentifikácie (TOTP 2FA).
 */
interface TwoFactorInterface
{
    /**
     * Aktivuje 2FA pre používateľa.
     *
     * @param User $user Používateľ.
     * @return string Tajný kľúč (secret) pre nastavenie v Authenticator app.
     */
    public function enableTwoFactor(User $user): string;

    /**
     * Deaktivuje 2FA pre používateľa.
     *
     * @param User $user Používateľ.
     */
    public function disableTwoFactor(User $user): void;

    /**
     * Zistí, či má používateľ aktivovanú 2FA.
     *
     * @param User $user Používateľ.
     * @return bool TRUE ak je 2FA aktivovaná.
     */
    public function isTwoFactorEnabled(User $user): bool;

    /**
     * Overí TOTP kód.
     *
     * @param User $user Používateľ.
     * @param string $code TOTP kód na overenie.
     * @return bool TRUE ak je kód platný.
     */
    public function verifyCode(User $user, string $code): bool;

    /**
     * Overí TOTP kód – vyhodí výnimku ak je neplatný.
     *
     * @param User $user Používateľ.
     * @param string $code TOTP kód na overenie.
     * @throws TwoFactorException Ak je kód neplatný.
     */
    public function requireValidCode(User $user, string $code): void;

    /**
     * Získa QR kód pre nastavenie 2FA.
     *
     * @param string $secret Tajný kľúč.
     * @param string $userEmail Email používateľa.
     * @param string $issuer Názov aplikácie (predvolene 'PaginiumCMS').
     * @return string QR kód v Base64.
     */
    public function getQRCode(string $secret, string $userEmail, string $issuer = 'PaginiumCMS'): string;

    /**
     * Získa URI pre nastavenie 2FA.
     *
     * @param string $secret Tajný kľúč.
     * @param string $userEmail Email používateľa.
     * @param string $issuer Názov aplikácie.
     * @return string URI pre Google Authenticator.
     */
    public function getProvisioningUri(string $secret, string $userEmail, string $issuer = 'PaginiumCMS'): string;

    /**
     * Získa aktuálny TOTP kód pre tajný kľúč.
     *
     * @param string $secret Tajný kľúč.
     * @return string Aktuálny TOTP kód.
     */
    public function getCurrentCode(string $secret): string;

    /**
     * Vygeneruje nový tajný kľúč.
     *
     * @return string Tajný kľúč.
     */
    public function generateSecret(): string;

    /**
     * Zistí, či bola TOTP overená v aktuálnej session.
     */
    public function isTotpVerified(): bool;

    /**
     * Zistí, či používateľ prešiel 2FA (alebo ju nemá zapnutú).
     */
    public function isTwoFactorPassed(User $user): bool;
}
