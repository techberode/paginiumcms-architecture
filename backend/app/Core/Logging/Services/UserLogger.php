<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;

/**
 * Špecializovaný logger pre používateľské aktivity.
 */
class UserLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Zaloguje aktivitu používateľa.
 * @param array<int|string, mixed> $details
 */public function log(
        string $userId,
        string $action,
        array $details = [],
        string $severity = 'INFO'
    ): void {
        $context = [
            'userId' => $userId,
            'action' => $action,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        $this->logger->log($severity, 'USER: ' . $action . ' (user: ' . $userId . ')', $context);
    }

    /**
     * Zaloguje prihlásenie používateľa.
     */
    public function login(string $userId, bool $success = true): void
    {
        $severity = $success ? 'INFO' : 'WARNING';
        $this->log($userId, 'login', ['success' => $success], $severity);
    }

    /**
     * Zaloguje odhlásenie používateľa.
     */
    public function logout(string $userId): void
    {
        $this->log($userId, 'logout', [], 'INFO');
    }

    /**
     * Zaloguje registráciu používateľa.
     */
    public function register(string $userId, string $email): void
    {
        $this->log($userId, 'register', ['email' => $email], 'INFO');
    }

    /**
     * Zaloguje zmenu profilu.
 * @param array<int|string, mixed> $changes
 */public function profileUpdate(string $userId, array $changes): void
    {
        $this->log($userId, 'profile_update', ['changes' => $changes], 'INFO');
    }

    /**
     * Zaloguje neúspešný pokus o prihlásenie.
     */
    public function failedLogin(string $email, string $ip): void
    {
        $this->logger->warning('USER: failed_login (email: ' . $email . ')', [
            'email' => $email,
            'ip' => $ip,
            'action' => 'failed_login',
        ]);
    }

    /**
     * Zaloguje zablokovanie používateľa.
     */
    public function block(string $userId, string $reason): void
    {
        $this->log($userId, 'block', ['reason' => $reason], 'CRITICAL');
    }

    /**
     * Zaloguje odblokovanie používateľa.
     */
    public function unblock(string $userId): void
    {
        $this->log($userId, 'unblock', [], 'INFO');
    }
}
