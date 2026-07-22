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
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Contracts\TOTPGeneratorInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;

use function DI\create;
use function DI\get;

return [
    // --- FlatFile Core ---
    FileValidator::class => create()
        ->constructor(__DIR__ . '/../storage/app/content'),

    FileReaderInterface::class => create(FileReader::class)
        ->constructor(get(FileValidator::class)),

    FileWriterInterface::class => create(FileWriter::class)
        ->constructor(get(FileValidator::class)),

    // --- Security Core ---
    SessionManager::class => create(SessionManager::class),

    PasswordPolicyInterface::class => create(PasswordPolicy::class)
        ->constructor(8, 72, true, true, true, true),

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

    AuthenticationInterface::class => create(AuthenticationManager::class)
        ->constructor(
            get(SessionManager::class),
            get(PasswordPolicyInterface::class),
            get(UserRepository::class)
        ),

    AuthorizationInterface::class => create(AuthorizationManager::class),

    CsrfProtectionInterface::class => create(CsrfProtectionManager::class)
        ->constructor(get(SessionManager::class)),

    TOTPGeneratorInterface::class => create(TOTPGenerator::class)
        ->constructor(30, 6, 'sha1'),

    QRCodeGenerator::class => create(QRCodeGenerator::class),

    TwoFactorInterface::class => create(TwoFactorManager::class)
        ->constructor(
            get(TOTPGeneratorInterface::class),
            get(QRCodeGenerator::class),
            get(UserRepository::class),
            get(SessionManager::class)
        ),
];
