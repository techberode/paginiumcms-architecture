<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Exception\AuthenticationException;

/**
 * Implementácia správy autentifikácie.
 */
class AuthenticationManager implements AuthenticationInterface
{
    private SessionManager $session;
    private PasswordPolicyInterface $passwordPolicy;
    private UserRepository $userRepository;

    public function __construct(
        SessionManager $session,
        PasswordPolicyInterface $passwordPolicy,
        UserRepository $userRepository
    ) {
        $this->session = $session;
        $this->passwordPolicy = $passwordPolicy;
        $this->userRepository = $userRepository;
    }

    public function login(string $email, string $password): User
    {
        // Normalizácia UTF-8
        $email = utf8_normalize($email);
        $password = utf8_normalize($password);

        // Validácia emailu
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AuthenticationException('Neplatný email');
        }


        $user = $this->userRepository->findByEmail($email);
        
        if ($user === null) {
            throw new AuthenticationException('Neplatný email alebo heslo');
        }

        if (!$user->verifyPassword($password)) {
            throw new AuthenticationException('Neplatný email alebo heslo');
        }

        if (!$user->isActive()) {
            throw new AuthenticationException('Účet je deaktivovaný');
        }

        $this->session->setUser($user);
        // Nové prihlásenie vždy vyžaduje čerstvé TOTP overenie (ak je 2FA zapnutá).
        $this->session->clearTotpVerified();

        return $user;
    }

    public function logout(): void
    {
        $this->session->clearUser();
        $this->session->clearTotpVerified();
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->session->ensureValid()) {
            return null;
        }

        return $this->session->getUser();
    }

    public function refreshCurrentUserFromStorage(): ?User
    {
        $current = $this->session->getUser();
        if ($current === null) {
            return null;
        }

        $fresh = $this->userRepository->findByEmail($current->getEmail());
        if ($fresh === null) {
            $this->session->clearUser();

            return null;
        }

        $this->session->updateUser($fresh);

        return $fresh;
    }

    public function isAuthenticated(): bool
    {
        if (!$this->session->ensureValid()) {
            return false;
        }

        return $this->session->isAuthenticated();
    }

    public function touchSession(): void
    {
        $this->session->touch();
    }

    public function changePassword(User $user, string $oldPassword, string $newPassword): void
    {
        if (!$user->verifyPassword($oldPassword)) {
            throw new AuthenticationException('Staré heslo nie je správne');
        }

        $this->passwordPolicy->requireValid($newPassword);
        
        $user->setPassword($newPassword);
        $this->userRepository->save($user);
    }

    public function resetPassword(string $email): string
    {
        $user = $this->userRepository->findByEmail($email);
        
        if ($user === null) {
            throw new AuthenticationException('Používateľ s týmto emailom neexistuje');
        }

        $token = bin2hex(random_bytes(32));
        $this->userRepository->saveResetToken($user, $token);
        
        return $token;
    }

    public function verifyResetToken(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findByResetToken($token);
        
        if ($user === null) {
            throw new AuthenticationException('Neplatný alebo expirovaný token');
        }

        $this->passwordPolicy->requireValid($newPassword);
        
        $user->setPassword($newPassword);
        $this->userRepository->save($user);
        $this->userRepository->clearResetToken($user);
    }
}
