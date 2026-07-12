<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Models\User;
use ParagonIE\ConstantTime\Base32;

class TwoFactorManager
{
    private UserRepository $userRepository;

    private int $window = 1;
    private int $period = 30;
    private int $digits = 6;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function generateSecret(): string
    {
        return Base32::encodeUpper(random_bytes(20));
    }

    public function enable(User $user, string $secret, string $code): bool
    {
        if (!$this->verifyCode($secret, $code)) {
            return false;
        }

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret($secret);
        $this->userRepository->save($user);

        return true;
    }

    public function disable(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        if ($secret === null) {
            return false;
        }

        if (!$this->verifyCode($secret, $code)) {
            return false;
        }

        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorSecret(null);
        $this->userRepository->save($user);

        return true;
    }

    public function verify(User $user, string $code): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return true;
        }

        $secret = $user->getTwoFactorSecret();
        if ($secret === null) {
            return false;
        }

        return $this->verifyCode($secret, $code);
    }

    public function verifyUserByEmail(string $email, string $code): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!$this->verify($user, $code)) {
            return null;
        }

        return $user;
    }

    public function generateCode(string $secret): string
    {
        $time = floor(time() / $this->period);
        $data = pack('N*', 0) . pack('N*', $time);

        $secretDecoded = Base32::decodeUpper($secret);
        $hash = hash_hmac('sha1', $data, $secretDecoded, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        $binary = (
            (ord($hash[$offset]) & 0x7F) << 24 |
            (ord($hash[$offset + 1]) & 0xFF) << 16 |
            (ord($hash[$offset + 2]) & 0xFF) << 8 |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binary % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $time = floor(time() / $this->period);

        for ($i = -$this->window; $i <= $this->window; $i++) {
            $timeWindow = $time + $i;
            $data = pack('N*', 0) . pack('N*', $timeWindow);

            $secretDecoded = Base32::decodeUpper($secret);
            $hash = hash_hmac('sha1', $data, $secretDecoded, true);

            $offset = ord($hash[strlen($hash) - 1]) & 0xF;
            $binary = (
                (ord($hash[$offset]) & 0x7F) << 24 |
                (ord($hash[$offset + 1]) & 0xFF) << 16 |
                (ord($hash[$offset + 2]) & 0xFF) << 8 |
                (ord($hash[$offset + 3]) & 0xFF)
            );

            $generatedCode = str_pad((string) ($binary % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);

            if (hash_equals($generatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    public function generateQrCodeUri(User $user, string $secret): string
    {
        $label = $user->getEmail();
        $issuer = 'PaginiumCMS';

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&digits=%d&period=%d',
            urlencode($label),
                       $secret,
                       urlencode($issuer),
                       $this->digits,
                       $this->period
        );
    }
}

    public function isTotpVerified(User $user): bool
    {
        return $user->isTwoFactorVerified();
    }
