<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Services;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;

class AuditLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(
        string $action,
        string $target,
        ?string $userId = null,
        array $details = [],
        string $severity = 'INFO'
    ): void {
        $context = [
            'action' => $action,
            'target' => $target,
            'userId' => $userId,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        $message = 'AUDIT: ' . $action . ' - ' . $target;
        $this->logger->log($severity, $message, $context);
    }

    public function contentAccess(string $userId, string $contentPath, string $action = 'view'): void
    {
        $this->log($action, $contentPath, $userId, ['type' => 'content'], 'INFO');
    }

    public function contentChange(string $userId, string $contentPath, array $changes): void
    {
        $this->log('update', $contentPath, $userId, ['changes' => $changes], 'WARNING');
    }

    public function login(string $userId, bool $success = true): void
    {
        $severity = $success ? 'INFO' : 'WARNING';
        $this->log('login', 'user/' . $userId, $userId, ['success' => $success], $severity);
    }

    public function passwordChange(string $userId): void
    {
        $this->log('password_change', 'user/' . $userId, $userId, [], 'WARNING');
    }

    public function twoFactorEnable(string $userId): void
    {
        $this->log('2fa_enable', 'user/' . $userId, $userId, [], 'INFO');
    }

    public function twoFactorDisable(string $userId): void
    {
        $this->log('2fa_disable', 'user/' . $userId, $userId, [], 'WARNING');
    }

    public function roleChange(string $userId, array $oldRoles, array $newRoles): void
    {
        $this->log(
            'role_change',
            'user/' . $userId,
            $userId,
            ['old_roles' => $oldRoles, 'new_roles' => $newRoles],
            'WARNING'
        );
    }

    public function contentDelete(string $userId, string $contentPath): void
    {
        $this->log('delete', $contentPath, $userId, [], 'ERROR');
    }

    public function system(string $message, string $severity = 'INFO'): void
    {
        $this->logger->log($severity, 'SYSTEM: ' . $message, ['type' => 'system']);
    }
}
