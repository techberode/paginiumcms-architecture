<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Tuupola\Middleware\CorsMiddleware;
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
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Http\Controllers\Auth\AuthController;
use PaginiumCMS\Http\Controllers\Auth\TwoFactorController;
use PaginiumCMS\Http\Controllers\Admin\BackupController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Core\Backup\Services\BackupManager;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;

require_once __DIR__ . '/utf8.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$app = AppFactory::create();

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([

    FileValidator::class => function () {
        return new FileValidator(__DIR__ . '/../storage/app/content');
    },

    FileReaderInterface::class => function ($container) {
        return new FileReader($container->get(FileValidator::class));
    },

    FileWriterInterface::class => function ($container) {
        return new FileWriter($container->get(FileValidator::class));
    },

    SessionManager::class => function () {
        return new SessionManager();
    },

    PasswordPolicyInterface::class => function () {
        return new PasswordPolicy(8, 72, true, true, true, true);
    },

    UserRepository::class => function ($container) {
        return new UserRepository(
            $container->get(FileReaderInterface::class),
                                  $container->get(FileWriterInterface::class),
                                  'data/users'
        );
    },

    AuthenticationInterface::class => function ($container) {
        return new AuthenticationManager(
            $container->get(SessionManager::class),
                                         $container->get(PasswordPolicyInterface::class),
                                         $container->get(UserRepository::class)
        );
    },

    AuthorizationInterface::class => function () {
        return new AuthorizationManager();
    },

    CsrfProtectionInterface::class => function ($container) {
        return new CsrfProtectionManager($container->get(SessionManager::class));
    },

    TOTPGeneratorInterface::class => function () {
        return new TOTPGenerator(30, 6, 'sha1');
    },

    QRCodeGenerator::class => function () {
        return new QRCodeGenerator();
    },

    TwoFactorInterface::class => function ($container) {
        return new TwoFactorManager(
            $container->get(TOTPGeneratorInterface::class),
                                    $container->get(QRCodeGenerator::class),
                                    $container->get(UserRepository::class),
                                    $container->get(SessionManager::class)
        );
    },

    AuthController::class => function ($container) {
        return new AuthController(
            $container->get(AuthenticationInterface::class),
                                  $container->get(AuthorizationInterface::class),
                                  $container->get(CsrfProtectionInterface::class),
                                  $container->get(PasswordPolicyInterface::class),
                                  $container->get(TwoFactorInterface::class),
                                  $container->get(UserRepository::class)
        );
    },

    TwoFactorController::class => function ($container) {
        return new TwoFactorController(
            $container->get(TwoFactorInterface::class),
                                       $container->get(UserRepository::class)
        );
    },

    AuthMiddleware::class => function ($container) {
        return new AuthMiddleware(
            $container->get(AuthenticationInterface::class)
        );
    },

    // --- Backup ---
    BackupInterface::class => function ($container) {
        return new BackupManager(
            $container->get(FileReaderInterface::class),
                                 $container->get(FileWriterInterface::class),
                                 __DIR__ . '/../storage/backups',
                                 __DIR__ . '/../storage/app/content'
        );
    },

    BackupController::class => function ($container) {
        return new BackupController(
            $container->get(BackupInterface::class)
        );
    },

]);

// Načítanie Logging služieb
$loggingServices = require_once __DIR__ . '/../app/Core/Logging/Config/services.php';
if (is_callable($loggingServices)) {
    $containerBuilder->addDefinitions($loggingServices);
}

$container = $containerBuilder->build();
AppFactory::setContainer($container);
$app = AppFactory::create();

// --- CORS ---
$app->add(new CorsMiddleware([
    "origin" => ["http://localhost:3025", "http://localhost:5173"],
    "methods" => ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    "headers.allow" => ["Content-Type", "Authorization", "X-CSRF-TOKEN", "Accept", "X-Requested-With"],
    "headers.expose" => [],
    "credentials" => true,
    "cache" => 86400,
]));

// --- ROUTY ---
$app->group('/api/auth', function (RouteCollectorProxy $group) use ($container) {
    $authController = $container->get(AuthController::class);
    $twoFactorController = $container->get(TwoFactorController::class);

    $group->post('/register', [$authController, 'register']);
    $group->post('/login', [$authController, 'login']);
    $group->post('/reset-password', [$authController, 'resetPassword']);
    $group->post('/verify-reset-token', [$authController, 'verifyResetToken']);
    $group->get('/csrf-token', [$authController, 'getCsrfToken']);

    $group->group('', function (RouteCollectorProxy $protected) use ($authController, $twoFactorController) {
        $protected->post('/logout', [$authController, 'logout']);
        $protected->post('/change-password', [$authController, 'changePassword']);
        $protected->get('/me', [$authController, 'getCurrentUser']);
        $protected->post('/2fa/enable', [$twoFactorController, 'enable']);
        $protected->post('/2fa/disable', [$twoFactorController, 'disable']);
        $protected->post('/2fa/verify', [$twoFactorController, 'verify']);
        $protected->get('/2fa/qr-code', [$twoFactorController, 'getQrCode']);
        $protected->get('/2fa/status', [$twoFactorController, 'getStatus']);
        $protected->post('/2fa/verify-login', [$twoFactorController, 'verifyLogin']);
    })->add($container->get(AuthMiddleware::class));

    $group->group('/admin', function (RouteCollectorProxy $admin) use ($container) {
        $admin->get('/users', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Admin endpoint - users management (TODO)'
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        });
    })->add(new RoleMiddleware(
        $container->get(AuthorizationInterface::class),
                               ['ADMIN', 'SUPER_ADMIN']
    ))->add($container->get(AuthMiddleware::class));
});

// --- BACKUP ROUTY ---
$app->group('/api/admin', function (RouteCollectorProxy $group) use ($container) {
    $backupController = $container->get(BackupController::class);

    $group->get('/backups', [$backupController, 'listBackups']);
    $group->post('/backups', [$backupController, 'createBackup']);
    $group->get('/backups/{id}/download', [$backupController, 'downloadBackup']);
    $group->post('/backups/{id}/restore', [$backupController, 'restoreBackup']);
    $group->delete('/backups/{id}', [$backupController, 'deleteBackup']);
})->add($container->get(AuthMiddleware::class));

// --- CONTENT ROUTES (PRIDAŤ PRED return $app;) ---
$app->get('/api/pages', function ($request, $response) {
    // Dočasné dáta - neskôr nahradíte reálnym repozitárom
    $pages = [
        [
            'id' => 1,
          'title' => 'Domovská stránka',
          'slug' => 'home',
          'content' => '<h1>Vitajte na PaginiumCMS</h1><p>Toto je testovacia stránka.</p>',
          'published' => true,
          'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
          'title' => 'O nás',
          'slug' => 'about',
          'content' => '<h1>O nás</h1><p>Informácie o projekte PaginiumCMS.</p>',
          'published' => true,
          'created_at' => date('Y-m-d H:i:s')
        ]
    ];

    $response->getBody()->write(json_encode($pages, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/pages/{slug}', function ($request, $response, $args) {
    $slug = $args['slug'];

    $pages = [
        'home' => [
            'id' => 1,
          'title' => 'Domovská stránka',
          'slug' => 'home',
          'content' => '<h1>Vitajte na PaginiumCMS</h1><p>Toto je testovacia stránka.</p>',
          'published' => true
        ],
        'about' => [
            'id' => 2,
          'title' => 'O nás',
          'slug' => 'about',
          'content' => '<h1>O nás</h1><p>Informácie o projekte PaginiumCMS.</p>',
          'published' => true
        ]
    ];

    if (!isset($pages[$slug])) {
        $response->getBody()->write(json_encode(['error' => 'Stránka nenájdená']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode($pages[$slug], JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/articles', function ($request, $response) {
    $articles = [
        [
            'id' => 1,
          'title' => 'Prvý článok',
          'slug' => 'first-article',
          'content' => '<h2>Prvý článok</h2><p>Toto je obsah prvého článku.</p>',
          'excerpt' => 'Úryvok prvého článku.',
          'date' => date('Y-m-d'),
          'published' => true
        ],
        [
            'id' => 2,
          'title' => 'Druhý článok',
          'slug' => 'second-article',
          'content' => '<h2>Druhý článok</h2><p>Toto je obsah druhého článku.</p>',
          'excerpt' => 'Úryvok druhého článku.',
          'date' => date('Y-m-d', strtotime('-1 day')),
          'published' => true
        ]
    ];

    $response->getBody()->write(json_encode($articles, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/articles/{slug}', function ($request, $response, $args) {
    $slug = $args['slug'];

    $articles = [
        'first-article' => [
            'id' => 1,
          'title' => 'Prvý článok',
          'slug' => 'first-article',
          'content' => '<h2>Prvý článok</h2><p>Toto je obsah prvého článku.</p>',
          'excerpt' => 'Úryvok prvého článku.',
          'date' => date('Y-m-d'),
          'published' => true
        ],
        'second-article' => [
            'id' => 2,
          'title' => 'Druhý článok',
          'slug' => 'second-article',
          'content' => '<h2>Druhý článok</h2><p>Toto je obsah druhého článku.</p>',
          'excerpt' => 'Úryvok druhého článku.',
          'date' => date('Y-m-d', strtotime('-1 day')),
          'published' => true
        ]
    ];

    if (!isset($articles[$slug])) {
        $response->getBody()->write(json_encode(['error' => 'Článok nenájdený']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode($articles[$slug], JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// Root endpoint - informácie o API
$app->get('/', function ($request, $response) {
    $data = [
        'name' => 'PaginiumCMS API',
        'version' => '2.0.0',
        'status' => 'running',
        'endpoints' => [
            'GET /' => 'Informácie o API',
          'GET /api/pages' => 'Zoznam všetkých stránok',
          'GET /api/pages/{slug}' => 'Detail stránky',
          'GET /api/articles' => 'Zoznam všetkých článkov',
          'GET /api/articles/{slug}' => 'Detail článku',
          'POST /api/auth/login' => 'Prihlásenie používateľa',
          'POST /api/auth/register' => 'Registrácia nového používateľa',
          'GET /api/auth/me' => 'Informácie o prihlásenom používateľovi',
          'GET /api/auth/csrf-token' => 'Získanie CSRF tokenu'
        ]
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

return $app;
