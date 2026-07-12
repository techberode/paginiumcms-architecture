<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Tuupola\Middleware\CorsMiddleware;

// ---------- BEZPEČNOSTNÉ MIDDLEWARE ----------
use PaginiumCMS\Http\Middleware\SecurityMiddleware;
use PaginiumCMS\Http\Middleware\RateLimitMiddleware;
use PaginiumCMS\Http\Middleware\LoginRateLimitMiddleware;

// ---------- PÔVODNÉ IMPORTY ----------
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
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Http\Controllers\Auth\AuthController;
use PaginiumCMS\Http\Controllers\Auth\TwoFactorController;
use PaginiumCMS\Http\Controllers\Admin\BackupController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Core\Backup\Services\BackupManager;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Logging\Services\Logger;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Security\SecurityLogger;

// ---------- NAČÍTANIE UTF-8 ----------
require_once __DIR__ . '/utf8.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// ---------- SESSION BEZPEČNOSŤ ----------
if (file_exists(__DIR__ . '/session.php')) {
    require_once __DIR__ . '/session.php';
}

// ---------- DI KONTENJNER ----------
$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([

    // ============================================
    // 1. FLATFILE CORE
    // ============================================

    FileValidator::class => function () {
        return new FileValidator(__DIR__ . '/../storage/app/content');
    },

    FileReaderInterface::class => function ($container) {
        return new FileReader($container->get(FileValidator::class));
    },

    FileWriterInterface::class => function ($container) {
        return new FileWriter($container->get(FileValidator::class));
    },

    // ============================================
    // 2. CACHE (BEZPEČNOSTNÁ VERZIA S RATE LIMITING)
    // ============================================

    FileDriver::class => function () {
        $cachePath = __DIR__ . '/../storage/cache';
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        return new FileDriver($cachePath);
    },

    CacheManager::class => function ($container) {
        return new CacheManager(
            $container->get(FileDriver::class),
                                'paginium_'
        );
    },

    // ============================================
    // 3. LOGGING
    //
    // OPRAVA (audit 12.7.2026, nález #8): pôvodne tu bol kód, ktorý
    // require_once-ol Core/Logging/Config/services.php, zavolal ho ako
    // closure a výsledok ($definitions) nikde nepoužil - vždy sa aj tak
    // vytvoril fallback Logger nižšie. Mŕtvy/mätúci kód odstránený,
    // ponechaná iba funkčná časť (fallback Logger je jediná reálne
    // použitá cesta, takže je teraz jediná).
    // ============================================

    LoggerInterface::class => function ($container) {
        $writer = new \PaginiumCMS\Core\Logging\Services\LogWriter(
            $container->get(FileReaderInterface::class),
                                                                   $container->get(FileWriterInterface::class),
                                                                   __DIR__ . '/../storage/logs/app'
        );
        return new Logger($writer, 'app');
    },

    // ============================================
    // 4. SECURITY LOGGER
    // ============================================

    SecurityLogger::class => function ($container) {
        return new SecurityLogger(
            $container->get(LoggerInterface::class),
                                  [
                                      'log_failed_logins' => true,
                                  'log_successful_logins' => true,
                                  'log_suspicious_activity' => true,
                                  'log_security_errors' => true,
                                  'alert_on_brute_force' => true,
                                  'alert_on_privilege_escalation' => true,
                                  ]
        );
    },

    // ============================================
    // 5. SECURITY MIDDLEWARE
    // ============================================

    SecurityMiddleware::class => function () {
        return new SecurityMiddleware([
            'hsts_max_age' => 31536000,
            'csp_default' => "default-src 'self'",
            'csp_script' => "script-src 'self' 'unsafe-inline'",
            'csp_style' => "style-src 'self' 'unsafe-inline'",
            'csp_img' => "img-src 'self' data: https:",
            'csp_font' => "font-src 'self' data:",
            'csp_connect' => "connect-src 'self'",
            'frame_options' => 'DENY',
            'xss_protection' => '1; mode=block',
            'content_type' => 'nosniff',
            'referrer_policy' => 'strict-origin-when-cross-origin',
            'remove_server_headers' => true,
        ]);
    },

    // OPRAVA (audit, nález #7): trustedProxies pridané - viď opravený
    // RateLimitMiddleware.php. Ak appka beží priamo (bez reverse proxy),
    // ponechajte trustedProxies prázdne pole - potom sa vždy použije
    // REMOTE_ADDR a X-Forwarded-For sa ignoruje (nedá sa sfalšovať).
    // Ak beží ZA nginx reverse proxy na tomto istom serveri, pridajte IP
    // toho proxy (zvyčajne 127.0.0.1, ak nginx a PHP-FPM bežia na tom
    // istom stroji/kontajneri).
    RateLimitMiddleware::class => function ($container) {
        return new RateLimitMiddleware(
            $container->get(CacheManager::class),
                                       maxRequests: (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 60),
                                       window: (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 60),
                                       excludedPaths: ['/api/health', '/api/test'],
                                       excludedIps: [],
                                       trustedProxies: array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1')))
        );
    },

    LoginRateLimitMiddleware::class => function ($container) {
        return new LoginRateLimitMiddleware(
            $container->get(CacheManager::class),
                                            trustedProxies: array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1')))
        );
    },

    // ============================================
    // 6. SESSION MANAGER (BEZPEČNÁ VERZIA)
    // ============================================

    SessionManager::class => function () {
        if (class_exists('\PaginiumCMS\Core\Security\SecureSessionManager')) {
            return new \PaginiumCMS\Core\Security\SecureSessionManager();
        }
        return new SessionManager();
    },

    // ============================================
    // 7. SECURITY CORE
    // ============================================

    PasswordPolicyInterface::class => function () {
        return new PasswordPolicy(
            minLength: 8,
            maxLength: 72,
            requireUppercase: true,
            requireLowercase: true,
            requireNumbers: true,
            requireSpecialChars: true
        );
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

    // ============================================
    // 8. CONTROLLERS
    // ============================================

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

    // ============================================
    // 9. BACKUP
    // ============================================

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

// ============================================
// 10. NAČÍTANIE LOGGING SLUŽIEB (Core moduly majú vlastný Config/services.php)
// ============================================

$loggingServices = require_once __DIR__ . '/../app/Core/Logging/Config/services.php';
if (is_callable($loggingServices)) {
    $containerBuilder->addDefinitions($loggingServices);
}

// ============================================
// 10b. NAČÍTANIE DI VÄZIEB PRE HTTP VRSTVU (nové API endpointy, oddelené
// od jadra - Content, Media, ...). PRIDANÉ pri oprave auditu 12.7.2026,
// nález #4: tieto väzby existovali z predchádzajúcich session, ale nikdy
// neboli sem zapojené, takže ContentController/MediaController sa nedali
// vôbec vytvoriť cez kontajner.
// ============================================

$httpServices = require_once __DIR__ . '/../app/Http/Config/services.php';
if (is_array($httpServices)) {
    $containerBuilder->addDefinitions($httpServices);
}

// ============================================
// 11. VYTVORENIE KONTEJNERA A APLIKÁCIE
// ============================================

$container = $containerBuilder->build();
AppFactory::setContainer($container);
$app = AppFactory::create();

// ============================================
// 12. CORS (BEZPEČNOSTNÁ VERZIA)
// ============================================

$corsAllowedOrigins = [
    'http://localhost:3025',
'http://localhost:5173',
];

if (getenv('APP_URL')) {
    $corsAllowedOrigins[] = getenv('APP_URL');
}

$app->add(new CorsMiddleware([
    "origin" => $corsAllowedOrigins,
    "methods" => ["GET", "POST", "PUT", "DELETE", "PATCH", "OPTIONS"],
    "headers.allow" => [
        "Content-Type",
        "Authorization",
        "X-CSRF-TOKEN",
        "Accept",
        "X-Requested-With",
        "X-RateLimit-Limit",
        "X-RateLimit-Remaining",
        "X-RateLimit-Reset"
    ],
    "headers.expose" => [
        "X-RateLimit-Limit",
        "X-RateLimit-Remaining",
        "X-RateLimit-Reset"
    ],
    "credentials" => true,
    "cache" => 86400,
]));

// ============================================
// 13. BEZPEČNOSTNÉ MIDDLEWARE (GLOBÁLNE)
// ============================================

$app->add($container->get(SecurityMiddleware::class));
$app->add($container->get(RateLimitMiddleware::class));

// ============================================
// 14. ROUTY
// ============================================

// ---------- AUTH ROUTY ----------
$app->group('/api/auth', function (RouteCollectorProxy $group) use ($container) {
    $authController = $container->get(AuthController::class);
    $twoFactorController = $container->get(TwoFactorController::class);

    $group->post('/register', [$authController, 'register']);

    $group->post('/login', [$authController, 'login'])
    ->add($container->get(LoginRateLimitMiddleware::class));

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

// ---------- BACKUP ROUTY ----------
$app->group('/api/admin', function (RouteCollectorProxy $group) use ($container) {
    $backupController = $container->get(BackupController::class);

    $group->get('/backups', [$backupController, 'listBackups']);
    $group->post('/backups', [$backupController, 'createBackup']);
    $group->get('/backups/{id}/download', [$backupController, 'downloadBackup']);
    $group->post('/backups/{id}/restore', [$backupController, 'restoreBackup']);
    $group->delete('/backups/{id}', [$backupController, 'deleteBackup']);
})->add($container->get(AuthMiddleware::class));

// ---------- HEALTH CHECK ----------
$app->get('/api/health', function ($request, $response) {
    $data = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
          'version' => '2.0.0',
          'php_version' => PHP_VERSION,
          'environment' => getenv('APP_ENV') ?? 'development'
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// ---------- ROOT ENDPOINT ----------
$app->get('/', function ($request, $response) {
    $data = [
        'name' => 'PaginiumCMS API',
        'version' => '2.0.0',
        'status' => 'running',
    ];
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

// ============================================
// 15. AUTO-DISCOVERY NOVÝCH MODULOV
//
// OPRAVA (audit 12.7.2026, nález #4 a #6): pôvodne tu bol natvrdo
// zapísaný blok "CONTENT ROUTY" s 2 fixnými fake stránkami a 2 fixnými
// fake článkami (žiadne reálne prepojenie na ContentRepositoryInterface).
// Rovnaký mock bol DUPLICITNE aj v backend/app/Http/Routes/content.php.
// Oba nahradené - skutočný ContentController + MediaController sa
// registrujú tu, automaticky, cez auto-discovery:
//
// Každý súbor v app/Http/Routes/*.php vracia function (App $app): void
// a je automaticky zaregistrovaný. Pridanie ĎALŠIEHO nového modulu
// (napr. navigation, settings) = pridanie súboru do tohto priečinka,
// ŽIADNA ďalšia zmena tu nie je potrebná.
//
// DÔLEŽITÉ: backend/app/Http/Routes/auth.php odporúčam ZMAZAŤ (je to
// tretia, navyše nefunkčná (chýbajúce importy Request/Response) kópia
// auth routes, ktorá by sa duplicitne registrovala popri bloku vyššie).
// ============================================

foreach (glob(__DIR__ . '/../app/Http/Routes/*.php') as $routeFile) {
    if (basename($routeFile) === 'auth.php') {
        // Duplicitná/nefunkčná kópia auth routes - viď poznámka vyššie.
        // Auth je už plne funkčný v bloku "AUTH ROUTY" vyššie v tomto súbore.
        continue;
    }
    $register = require $routeFile;
    if (is_callable($register)) {
        $register($app);
    }
}

// ---------- FAVICON ----------
$app->get('/favicon.ico', function ($request, $response) {
    return $response->withStatus(204);
});

// Catch-all pre 404 (posledný)
$app->any('/{path:.*}', function ($request, $response) {
    $path = $request->getUri()->getPath();

    if ($path === '/favicon.ico') {
        return $response->withStatus(204);
    }

    $response->getBody()->write(json_encode([
        'error' => 'Endpoint nenájdený',
        'path' => $path
    ], JSON_PRETTY_PRINT));

    return $response
    ->withStatus(404)
    ->withHeader('Content-Type', 'application/json');
});

// Pridanie CORS Middleware pre lokálny vývoj
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    // Tieto hlavičky dovolia Reactu bezpečne čítať API odpovede
    return $response
    ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
    ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-CSRF-Token')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
    ->withHeader('Access-Control-Allow-Credentials', 'true'); // Dôležité pre cookies/session
});

// Ošetrenie predbežných OPTIONS požiadaviek, ktoré prehliadač automaticky posiela
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

return $app;
