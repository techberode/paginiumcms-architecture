<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Exception\SecurityException;

/**
 * Implementácia politiky hesiel.
 */
class PasswordPolicy implements PasswordPolicyInterface
{
    private int $minLength;
    private int $maxLength;
    private bool $requireUppercase;
    private bool $requireLowercase;
    private bool $requireNumbers;
    private bool $requireSpecialChars;

    public function __construct(
        int $minLength = 8,
        int $maxLength = 72,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = false
    ) {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }

    public function validate(string $password): bool
    {
        if (strlen($password) < $this->minLength) {
            return false;
        }

        if (strlen($password) > $this->maxLength) {
            return false;
        }

        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if ($this->requireSpecialChars && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    public function requireValid(string $password): void
    {
        if (!$this->validate($password)) {
            $errors = [];

            if (strlen($password) < $this->minLength) {
                $errors[] = sprintf('Heslo musí mať aspoň %d znakov', $this->minLength);
            }

            if (strlen($password) > $this->maxLength) {
                $errors[] = sprintf('Heslo môže mať maximálne %d znakov', $this->maxLength);
            }

            if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Heslo musí obsahovať aspoň jedno veľké písmeno';
            }

            if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
                $errors[] = 'Heslo musí obsahovať aspoň jedno malé písmeno';
            }

            if ($this->requireNumbers && !preg_match('/[0-9]/', $password)) {
                $errors[] = 'Heslo musí obsahovať aspoň jednu číslicu';
            }

            if ($this->requireSpecialChars && !preg_match('/[^a-zA-Z0-9]/', $password)) {
                $errors[] = 'Heslo musí obsahovať aspoň jeden špeciálny znak';
            }

            throw new SecurityException('Heslo nespĺňa požiadavky: ' . implode(', ', $errors));
        }
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    public function requiresUppercase(): bool
    {
        return $this->requireUppercase;
    }

    public function requiresLowercase(): bool
    {
        return $this->requireLowercase;
    }

    public function requiresNumbers(): bool
    {
        return $this->requireNumbers;
    }

    public function requiresSpecialChars(): bool
    {
        return $this->requireSpecialChars;
    }
}
