<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Contracts\TOTPGeneratorInterface;
use OTPHP\TOTP;

class TOTPGenerator implements TOTPGeneratorInterface
{
    private int $period;
    private int $digits;
    private string $digest;

    public function __construct(int $period = 30, int $digits = 6, string $digest = 'sha1')
    {
        $this->period = $period;
        $this->digits = $digits;
        $this->digest = $digest;
    }

    public function generateSecret(): string
    {
        $totp = TOTP::generate(
            null, // secret (necháme vygenerovať)
        $this->period,
        $this->digest,
        $this->digits
        );
        return $totp->getSecret();
    }

    public function getCurrentCode(string $secret): string
    {
        $totp = $this->createTOTP($secret);
        return $totp->now();
    }

    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        try {
            $totp = $this->createTOTP($secret);
            return $totp->verify($code, null, $window);
        } catch (\Exception) {
            return false;
        }
    }

    public function getProvisioningUri(string $secret, string $user, string $issuer = 'PaginiumCMS'): string
    {
        // Vytvoríme TOTP inštanciu a explicitne nastavíme label a issuer
        $totp = TOTP::create(
            $secret,
            $this->period,
            $this->digest,
            $this->digits
        );

        // Nastavíme label (email) a issuer (názov aplikácie)
        $totp->setLabel($user);
        $totp->setIssuer($issuer);

        return $totp->getProvisioningUri();
    }

    /**
     * Vytvorí TOTP inštanciu s tajným kľúčom.
     */
    private function createTOTP(string $secret): TOTP
    {
        return TOTP::create(
            $secret,
            $this->period,
            $this->digest,
            $this->digits
        );
    }
}
