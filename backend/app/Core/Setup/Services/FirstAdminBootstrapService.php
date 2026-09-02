<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Setup\Services;

use InvalidArgumentException;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;

/**
 * Creates the first SUPER_ADMIN when no accounts exist (shared by CLI + setup wizard).
 */
final class FirstAdminBootstrapService
{
    public function __construct(
        private UserRepository $users,
        private PasswordPolicyInterface $passwordPolicy,
    ) {
    }

    public function hasUsers(): bool
    {
        return $this->users->findAll() !== [];
    }

    public function createFirstAdmin(string $email, string $password, string $name): User
    {
        if ($this->hasUsers()) {
            throw new InvalidArgumentException('Admin bootstrap skipped: users already exist.');
        }

        $email = trim($email);
        $name = trim($name);

        if ($email === '' || $name === '') {
            throw new InvalidArgumentException('Email and name are required.');
        }

        $this->passwordPolicy->requireValid($password);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setName($name);
        $user->setRoles(['SUPER_ADMIN', 'ADMIN', 'EDITOR']);

        $this->users->save($user);

        return $user;
    }
}
