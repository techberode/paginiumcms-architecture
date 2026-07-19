<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

/**
 * Global 2FA/TOTP policy — optional dev bypass via .env.
 *
 * TWO_FACTOR_REQUIRED=false vypne vynútenie TOTP len v neprodukčnom APP_ENV
 * (development, local, testing). Na produkcii sa 2FA vždy vyžaduje.
 */
final class TwoFactorPolicy
{
    /** @var list<string> */
    private const NON_PRODUCTION_ENVS = ['development', 'local', 'testing', 'test'];

    public static function isRequired(): bool
    {
        $raw = getenv('TWO_FACTOR_REQUIRED') ?: ($_ENV['TWO_FACTOR_REQUIRED'] ?? null);
        if ($raw === null || $raw === '') {
            return true;
        }

        $required = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        if ($required) {
            return true;
        }

        $appEnv = strtolower((string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production')));

        return !in_array($appEnv, self::NON_PRODUCTION_ENVS, true);
    }
}
