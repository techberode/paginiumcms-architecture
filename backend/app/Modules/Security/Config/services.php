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
use PaginiumCMS\Modules\Security\Services\UserIndexService;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Services\ApiKeyStore;
use PaginiumCMS\Modules\Security\Services\ApiKeyVerifier;
use PaginiumCMS\Modules\Security\Services\ApiScopePolicy;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Contracts\TOTPGeneratorInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;

use function DI\create;
use function DI\get;

return [
    // Session Manager
    SessionManager::class => create(SessionManager::class),

    // Password Policy
    PasswordPolicyInterface::class => create(PasswordPolicy::class)
        ->constructor(8, 72, true, true, true, true),

    // User index + repository (PERF-USERREPO / ISS-057)
    UserIndexService::class => create(UserIndexService::class)
        ->constructor(
            get(FileReaderInterface::class),
            'data/index/users.json'
        ),

    UserRepository::class => create(UserRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/users',
            get(\PaginiumCMS\Core\Security\Services\EncryptionService::class),
            get(UserIndexService::class)
        ),

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

    ApiKeyStore::class => create(ApiKeyStore::class)
        ->constructor(get(FileReaderInterface::class)),

    ApiKeyVerifier::class => function (\Psr\Container\ContainerInterface $container): ApiKeyVerifier {
        $pepper = (string) (getenv('API_KEY_PEPPER') ?: ($_ENV['API_KEY_PEPPER'] ?? ''));
        if ($pepper === '' && (getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing')) {
            $pepper = 'paginium-test-api-key-pepper';
        }

        return new ApiKeyVerifier($container->get(ApiKeyStore::class), $pepper);
    },

    ApiScopePolicy::class => create(ApiScopePolicy::class),
];
