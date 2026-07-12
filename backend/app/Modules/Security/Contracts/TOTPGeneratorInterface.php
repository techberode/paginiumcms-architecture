<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Contracts;

/**
 * Rozhranie pre generovanie a overovanie TOTP kódov.
 */
interface TOTPGeneratorInterface
{
    /**
     * Vygeneruje tajný kľúč pre TOTP.
     *
     * @return string Tajný kľúč (Base32).
     */
    public function generateSecret(): string;

    /**
     * Získa aktuálny TOTP kód.
     *
     * @param string $secret Tajný kľúč.
     * @return string 6-miestny TOTP kód.
     */
    public function getCurrentCode(string $secret): string;

    /**
     * Overí TOTP kód.
     *
     * @param string $secret Tajný kľúč.
     * @param string $code Kód na overenie.
     * @param int $window Počet krokov do minulosti/budúcnosti (predvolene 1).
     * @return bool TRUE ak je kód platný.
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool;

    /**
     * Získa URI pre Google Authenticator.
     *
     * @param string $secret Tajný kľúč.
     * @param string $user Email používateľa.
     * @param string $issuer Názov aplikácie.
     * @return string URI.
     */
    public function getProvisioningUri(string $secret, string $user, string $issuer = 'PaginiumCMS'): string;
}
