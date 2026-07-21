<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;

/**
 * Politika hesiel odvodená z administrácie (skupina security).
 */
final class SettingsBackedPasswordPolicy implements PasswordPolicyInterface
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    public function validate(string $password): bool
    {
        return $this->delegate()->validate($password);
    }

    public function requireValid(string $password): void
    {
        $this->delegate()->requireValid($password);
    }

    public function getMinLength(): int
    {
        return $this->delegate()->getMinLength();
    }

    public function getMaxLength(): int
    {
        return $this->delegate()->getMaxLength();
    }

    public function requiresUppercase(): bool
    {
        return $this->delegate()->requiresUppercase();
    }

    public function requiresLowercase(): bool
    {
        return $this->delegate()->requiresLowercase();
    }

    public function requiresNumbers(): bool
    {
        return $this->delegate()->requiresNumbers();
    }

    public function requiresSpecialChars(): bool
    {
        return $this->delegate()->requiresSpecialChars();
    }

    private function delegate(): PasswordPolicy
    {
        $security = $this->settings->get('security') ?? [];

        $minLength = max(4, (int) ($security['passwordMinLength'] ?? 8));
        $maxLength = max($minLength, min(128, (int) ($security['passwordMaxLength'] ?? 72)));

        return new PasswordPolicy(
            minLength: $minLength,
            maxLength: $maxLength,
            requireUppercase: (bool) ($security['passwordRequireUppercase'] ?? true),
            requireLowercase: (bool) ($security['passwordRequireLowercase'] ?? true),
            requireNumbers: (bool) ($security['passwordRequireNumbers'] ?? true),
            requireSpecialChars: (bool) ($security['passwordRequireSpecialChars'] ?? true)
        );
    }
}
