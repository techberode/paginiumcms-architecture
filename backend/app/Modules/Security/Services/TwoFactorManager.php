<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Contracts\TOTPGeneratorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\TwoFactorException;

class TwoFactorManager implements TwoFactorInterface
{
    private TOTPGeneratorInterface $totpGenerator;
    private QRCodeGenerator $qrCodeGenerator;
    private UserRepository $userRepository;
    private SessionManager $session;

    public function __construct(
        TOTPGeneratorInterface $totpGenerator,
        QRCodeGenerator $qrCodeGenerator,
        UserRepository $userRepository,
        SessionManager $session
    ) {
        $this->totpGenerator = $totpGenerator;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->userRepository = $userRepository;
        $this->session = $session;
    }

    public function enableTwoFactor(User $user): string
    {
        $secret = $this->totpGenerator->generateSecret();

        $user->setTwoFactorSecret($secret);
        $user->setTwoFactorEnabled(true);
        $this->userRepository->save($user);

        // Overíme, či sa uloženie podarilo
       // $saved = $this->userRepository->findByEmail($user->getEmail());
       // if ($saved === null || !$saved->isTwoFactorEnabled()) {
       //     throw new TwoFactorException('Nepodarilo sa uložiť nastavenia 2FA');
       // }

        return $secret;
    }

    public function disableTwoFactor(User $user): void
    {
        $user->setTwoFactorSecret(null);
        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorVerifiedAt(null);
        $this->userRepository->save($user);
        
        $this->session->clearTotpVerified();
    }

    public function isTwoFactorEnabled(User $user): bool
    {
        return $user->isTwoFactorEnabled();
    }

    public function verifyCode(User $user, string $code): bool
    {
        // Načítame čerstvé dáta z repozitára
        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            throw new TwoFactorException('Používateľ nebol nájdený');
        }

        if (!$freshUser->isTwoFactorEnabled()) {
            throw new TwoFactorException('Dvojfaktorová autentifikácia nie je aktivovaná');
        }

        $secret = $freshUser->getTwoFactorSecret();

        if ($secret === null) {
            throw new TwoFactorException('Tajný kľúč pre 2FA nie je nastavený');
        }

        $isValid = $this->totpGenerator->verifyCode($secret, $code);

        if ($isValid) {
            $freshUser->setTwoFactorVerifiedAt(time());
            $this->userRepository->save($freshUser);
            $this->session->setTotpVerified();
        }

        return $isValid;
    }

    public function requireValidCode(User $user, string $code): void
    {
        if (!$this->verifyCode($user, $code)) {
            throw new TwoFactorException('Neplatný TOTP kód');
        }
    }

    public function getQRCode(string $secret, string $userEmail, string $issuer = 'PaginiumCMS'): string
    {
        $provisioningUri = $this->getProvisioningUri($secret, $userEmail, $issuer);
        return $this->qrCodeGenerator->generate($provisioningUri);
    }

    public function getProvisioningUri(string $secret, string $userEmail, string $issuer = 'PaginiumCMS'): string
    {
        return $this->totpGenerator->getProvisioningUri($secret, $userEmail, $issuer);
    }

    public function getCurrentCode(string $secret): string
    {
        return $this->totpGenerator->getCurrentCode($secret);
    }

    public function generateSecret(): string
    {
        return $this->totpGenerator->generateSecret();
    }

    public function isTotpVerified(): bool
    {
        return $this->session->isTotpVerified();
    }

    public function isTwoFactorPassed(User $user): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return true;
        }

        return $this->isTotpVerified();
    }
}
