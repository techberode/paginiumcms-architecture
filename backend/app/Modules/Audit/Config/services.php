<?php

declare(strict_types=1);

use PaginiumCMS\Modules\Security\Services\AuthenticationManager;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\CsrfProtectionManager;
use PaginiumCMS\Modules\Security\Services\PasswordPolicy;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Services\TOTPGenerator;
use PaginiumCMS\Modules\Security\Services\QRCodeGenerator;
use PaginiumCMS\Modules\Security\Services\TwoFactorManager;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Contracts\TOTPGeneratorInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Audit\Services\AuditEngine;
use PaginiumCMS\Modules\Audit\Services\SecurityAuditor;
use PaginiumCMS\Modules\Audit\Services\IntegrityAuditor;
use PaginiumCMS\Modules\Audit\Services\ConfigurationAuditor;
use PaginiumCMS\Modules\Audit\Services\CompatibilityAuditor;
use PaginiumCMS\Modules\Audit\Services\PerformanceAuditor;
use PaginiumCMS\Modules\Audit\Commands\RunAuditCommand;
use PaginiumCMS\Modules\Audit\Contracts\AuditEngineInterface;

use function DI\create;
use function DI\get;

return [
    // Session Manager
    SessionManager::class => create(SessionManager::class),

    // Password Policy
    PasswordPolicyInterface::class => create(PasswordPolicy::class)
    ->constructor(8, 72, true, true, true, true),

    // User Repository
    UserRepository::class => create(UserRepository::class)
    ->constructor(
        get(FileReaderInterface::class),
                  get(FileWriterInterface::class),
                  'data/users'
    ),

// Auditors
    SecurityAuditor::class => create(SecurityAuditor::class)
        ->constructor(__DIR__ . '/../../../../'),

    IntegrityAuditor::class => create(IntegrityAuditor::class)
        ->constructor(__DIR__ . '/../../../../', 'storage/checksums.json'),

    ConfigurationAuditor::class => create(ConfigurationAuditor::class)
        ->constructor(__DIR__ . '/../../../../'),

    CompatibilityAuditor::class => create(CompatibilityAuditor::class)
        ->constructor(__DIR__ . '/../../../../'),

    PerformanceAuditor::class => create(PerformanceAuditor::class)
        ->constructor(__DIR__ . '/../../../../', 104857600, 52428800),

    // Audit Engine
    AuditEngineInterface::class => create(AuditEngine::class)
        ->constructor([
            get(SecurityAuditor::class),
            get(IntegrityAuditor::class),
            get(ConfigurationAuditor::class),
            get(CompatibilityAuditor::class),
            get(PerformanceAuditor::class),
        ]),

    // Command
    RunAuditCommand::class => create(RunAuditCommand::class)
        ->constructor(get(AuditEngineInterface::class)),

// Authentication
AuthenticationInterface::class => create(AuthenticationManager::class)
->constructor(
    get(SessionManager::class),
              get(PasswordPolicyInterface::class),
              get(UserRepository::class)
),

// Authorization
AuthorizationInterface::class => create(AuthorizationManager::class),

// CSRF Protection
CsrfProtectionInterface::class => create(CsrfProtectionManager::class)
->constructor(get(SessionManager::class)),

// TOTP Generator (správny digest)
TOTPGeneratorInterface::class => create(TOTPGenerator::class)
->constructor(30, 6, 'sha1'),

// QR Code Generator
QRCodeGenerator::class => create(QRCodeGenerator::class),

// Two Factor
TwoFactorInterface::class => create(TwoFactorManager::class)
->constructor(
    get(TOTPGeneratorInterface::class),
              get(QRCodeGenerator::class),
              get(UserRepository::class),
              get(SessionManager::class)
),
];
