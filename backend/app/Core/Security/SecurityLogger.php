<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;

/**
 * Špecializovaný logger pre bezpečnostné udalosti.
 */
final class SecurityLogger
{
    private LoggerInterface $logger;
    /** @var array<int|string, mixed> */
    private array $config;

    /**
     * @param array<int|string, mixed> $config
     */
    public function __construct(
        LoggerInterface $logger,
        private ?LoginAttemptTracker $loginAttempts = null,
        private ?IncidentNotifier $incidentNotifier = null,
        private ?SecurityAuditStore $securityAudit = null,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->config = array_merge([
            'log_failed_logins' => true,
            'log_successful_logins' => true,
            'log_suspicious_activity' => true,
            'log_security_errors' => true,
            'alert_on_brute_force' => true,
            'alert_on_privilege_escalation' => true,
        ], $config);
    }

    /**
     * Zaloguje neúspešný pokus o prihlásenie.
     */
    public function logFailedLogin(string $email, string $ip, ?string $userAgent = null): void
    {
        if (!$this->config['log_failed_logins']) {
            return;
        }

        $this->logger->warning('Security: Failed login attempt', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'failed_login',
        ]);

        $this->securityAudit?->append(
            'failed_login',
            LogSeverity::WARNING,
            'Failed login attempt',
            null,
            $email,
            $ip,
            ['user_agent' => $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown']
        );

        // Alert pri opakovaných pokusoch
        if ($this->config['alert_on_brute_force']) {
            $this->checkBruteForceAttempt($ip, $email);
        }
    }

    /**
     * Zaloguje úspešné prihlásenie.
     */
    public function logSuccessfulLogin(string $userId, string $email, string $ip): void
    {
        if (!$this->config['log_successful_logins']) {
            return;
        }

        $this->logger->info('Security: Successful login', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => $ip,
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'successful_login',
        ]);

        $this->securityAudit?->append(
            'successful_login',
            LogSeverity::INFO,
            'Successful login',
            $userId,
            $email,
            $ip
        );
    }

    /**
     * Zaloguje zmenu hesla.
     */
    public function logPasswordChange(string $userId, string $email): void
    {
        $this->logger->warning('Security: Password changed', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'password_change',
        ]);
    }

    /**
     * Zaloguje zmenu rolí.
 * @param array<int|string, mixed> $oldRoles
 * @param array<int|string, mixed> $newRoles
 */public function logRoleChange(string $userId, string $email, array $oldRoles, array $newRoles): void
    {
        $this->logger->critical('Security: Role changed', [
            'user_id' => $userId,
            'email' => $email,
            'old_roles' => $oldRoles,
            'new_roles' => $newRoles,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'role_change',
        ]);

        if ($this->config['alert_on_privilege_escalation']) {
            $this->checkPrivilegeEscalation($userId, $oldRoles, $newRoles);
        }
    }

    /**
     * Zaloguje podozrivú aktivitu.
     */
    public function logSuspiciousActivity(
        string $action,
        string $details,
        string $severity = LogSeverity::WARNING
    ): void {
        if (!$this->config['log_suspicious_activity']) {
            return;
        }

        $this->logger->log($severity, 'Security: Suspicious activity - ' . $action, [
            'action' => $action,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'suspicious_activity',
        ]);
    }

    /**
     * Zaloguje bezpečnostnú chybu.
 * @param array<int|string, mixed> $context
 */public function logSecurityError(\Throwable $e, array $context = []): void
    {
        if (!$this->config['log_security_errors']) {
            return;
        }

        $this->logger->error('Security: Error - ' . $e->getMessage(), array_merge([
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'security_error',
        ], $context));
    }

    /**
     * Kontrola brute force útoku.
     */
    private function checkBruteForceAttempt(string $ip, string $email): void
    {
        if ($this->loginAttempts === null) {
            return;
        }

        $locked = $this->loginAttempts->recordFailure($ip, $email);
        if ($locked) {
            $this->logger->critical('Security: Brute-force lockout triggered', [
                'email' => $email,
                'ip' => $ip,
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'brute_force_lockout',
            ]);

            $this->incidentNotifier?->notifyLoginLockout($email, $ip);
        }
    }

    public function recordFailedLogin(string $ip, string $email, ?string $userAgent = null): void
    {
        $this->logFailedLogin($email, $ip, $userAgent);
    }

    public function recordSuccessfulLogin(string $userId, string $email, string $ip): void
    {
        $this->loginAttempts?->clearSuccess($ip, $email);
        $this->logSuccessfulLogin($userId, $email, $ip);
    }

    public function logPermissionDenied(User $user, string $permission, ?string $path = null): void
    {
        $this->securityAudit?->append(
            'permission_denied',
            LogSeverity::WARNING,
            sprintf('Permission denied: %s', $permission),
            $user->getId(),
            $user->getEmail(),
            null,
            ['permission' => $permission, 'path' => $path]
        );
    }

    public function logSettingsChange(User $user, string $group): void
    {
        $this->securityAudit?->append(
            'settings_change',
            LogSeverity::INFO,
            sprintf('Settings group updated: %s', $group),
            $user->getId(),
            $user->getEmail(),
            null,
            ['group' => $group]
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    public function logCachePurge(User $user, string $scope, array $result): void
    {
        $this->securityAudit?->append(
            'cache_purge',
            LogSeverity::INFO,
            sprintf('Manual cache purge: %s', $scope),
            $user->getId(),
            $user->getEmail(),
            null,
            ['scope' => $scope, 'result' => $result]
        );
    }

    public function logSsoLogin(User $user, string $provider, string $ip): void
    {
        $this->loginAttempts?->clearSuccess($ip, $user->getEmail());

        $this->logger->info('Security: SSO login', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'provider' => $provider,
            'ip' => $ip,
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'sso_login',
        ]);

        $this->securityAudit?->append(
            'sso_login',
            LogSeverity::INFO,
            sprintf('SSO login via %s', $provider),
            $user->getId(),
            $user->getEmail(),
            $ip,
            ['provider' => $provider]
        );
    }

    /**
     * Kontrola eskalácie privilégií.
 * @param array<int|string, mixed> $oldRoles
 * @param array<int|string, mixed> $newRoles
 */private function checkPrivilegeEscalation(string $userId, array $oldRoles, array $newRoles): void
    {
        // Kontrola, či boli pridané administrátorské roly
        $adminRoles = ['ADMIN', 'SUPER_ADMIN'];
        
        foreach ($adminRoles as $role) {
            if (in_array($role, $newRoles, true) && !in_array($role, $oldRoles, true)) {
                $this->logger->critical('Security: Privilege escalation detected!', [
                    'user_id' => $userId,
                    'new_role' => $role,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => 'privilege_escalation',
                ]);
                
                // TODO: Odoslať notifikáciu administrátorovi
            }
        }
    }
}
