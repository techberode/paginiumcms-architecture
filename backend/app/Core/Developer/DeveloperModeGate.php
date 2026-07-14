<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Developer;

use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Brána Developer Mode – predvolene ZAMKNUTÝ.
 *
 * DEVELOPER_MODE=true v .env len povolí možnosť odomknutia (nie automatický prístup).
 * Odomknutie:
 *   1. TOTP kód prihláseného admina s aktivovanou 2FA
 *   2. Registrovaný offline token (DevTokenGenerator + DevTokenRegistry)
 *
 * Stav odomknutia je v session (nulová disk I/O počas bežných requestov).
 */
class DeveloperModeGate
{
    private const SESSION_KEY = 'paginium_dev_unlocked_until';
    private const SESSION_METHOD = 'paginium_dev_unlock_method';
    private const DEFAULT_TTL = 28800; // 8 hodín

    public function __construct(
        private DevTokenGenerator $tokenGenerator,
        private DevTokenRegistry $tokenRegistry,
        private int $unlockTtlSeconds = self::DEFAULT_TTL
    ) {
    }

    /**
     * Či je dev mode vôbec povolený v konfigurácii (env).
     */
    public function isFeatureAvailable(): bool
    {
        return getenv('DEVELOPER_MODE') === 'true' || getenv('APP_DEBUG') === 'true';
    }

    public function isUnlocked(): bool
    {
        if (!$this->isFeatureAvailable()) {
            return false;
        }

        $until = $_SESSION[self::SESSION_KEY] ?? 0;

        return is_int($until) && $until > time();
    }

    public function lock(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::SESSION_METHOD]);
    }

    public function unlockWithTotp(User $user, string $code, TwoFactorInterface $twoFactor): bool
    {
        if (!$this->isFeatureAvailable()) {
            return false;
        }

        if (!$user->isTwoFactorEnabled()) {
            return false;
        }

        if (!$twoFactor->verifyCode($user, $code)) {
            return false;
        }

        $this->grantUnlock('totp');

        return true;
    }

    public function unlockWithToken(string $token): bool
    {
        if (!$this->isFeatureAvailable()) {
            return false;
        }

        $result = $this->tokenGenerator->validate($token, $this->tokenRegistry);
        if (!$result['valid']) {
            return false;
        }

        $this->grantUnlock('token:' . ($result['label'] ?? 'unknown'));

        $hash = hash('sha256', $token);
        $entry = $this->tokenRegistry->findByHash($hash);
        if ($entry !== null && ($entry['single_use'] ?? true)) {
            $this->tokenGenerator->markUsed($token, $this->tokenRegistry);
        }

        return true;
    }

    public function getUnlockMethod(): ?string
    {
        return $_SESSION[self::SESSION_METHOD] ?? null;
    }

    public function getStatus(): array
    {
        return [
            'feature_available' => $this->isFeatureAvailable(),
            'unlocked' => $this->isUnlocked(),
            'unlocked_until' => $_SESSION[self::SESSION_KEY] ?? null,
            'method' => $this->getUnlockMethod(),
        ];
    }

    private function grantUnlock(string $method): void
    {
        $_SESSION[self::SESSION_KEY] = time() + $this->unlockTtlSeconds;
        $_SESSION[self::SESSION_METHOD] = $method;
    }
}
