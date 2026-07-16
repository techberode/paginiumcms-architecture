<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use InvalidArgumentException;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use PaginiumCMS\Modules\Security\Contracts\TOTPGeneratorInterface;
use Psr\Clock\ClockInterface;

class TOTPGenerator implements TOTPGeneratorInterface
{
    private int $period;
    private int $digits;
    /** @var non-empty-string */
    private string $digest;
    private ClockInterface $clock;

    public function __construct(
        int $period = 30,
        int $digits = 6,
        string $digest = 'sha1',
        ?ClockInterface $clock = null
    ) {
        if ($period < 1) {
            throw new InvalidArgumentException('TOTP period must be at least 1.');
        }
        if ($digits < 1) {
            throw new InvalidArgumentException('TOTP digits must be at least 1.');
        }
        if ($digest === '') {
            throw new InvalidArgumentException('TOTP digest must not be empty.');
        }

        $this->period = $period;
        $this->digits = $digits;
        $this->digest = $digest;
        $this->clock = $clock ?? new InternalClock();
    }

    public function generateSecret(): string
    {
        $totp = TOTP::generate($this->clock);
        $this->applyConfiguration($totp);

        return $totp->getSecret();
    }

    public function getCurrentCode(string $secret): string
    {
        $totp = $this->createTOTP($secret);

        return $totp->now();
    }

    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if ($code === '' || $window < 0) {
            return false;
        }

        try {
            $totp = $this->createTOTP($secret);

            return $totp->verify($code, null, $window);
        } catch (\Exception) {
            return false;
        }
    }

    public function getProvisioningUri(string $secret, string $user, string $issuer = 'PaginiumCMS'): string
    {
        if ($user === '') {
            throw new InvalidArgumentException('TOTP user label must not be empty.');
        }

        $issuerName = $issuer !== '' ? $issuer : 'PaginiumCMS';
        $totp = $this->createTOTP($secret);
        $totp->setLabel($user);
        $totp->setIssuer($issuerName);

        return $totp->getProvisioningUri();
    }

    private function createTOTP(string $secret): TOTP
    {
        if ($secret === '') {
            throw new InvalidArgumentException('TOTP secret must not be empty.');
        }

        $totp = TOTP::createFromSecret($secret, $this->clock);
        $this->applyConfiguration($totp);

        return $totp;
    }

    private function applyConfiguration(TOTP $totp): void
    {
        $totp->setPeriod($this->period);
        $totp->setDigest($this->digest);
        $totp->setDigits($this->digits);
    }
}
