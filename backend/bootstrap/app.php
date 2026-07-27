<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Middleware\SameOriginCorsMiddleware;

// ---------- BEZPEČNOSTNÉ MIDDLEWARE ----------
use PaginiumCMS\Http\Middleware\SecurityMiddleware;
use PaginiumCMS\Http\Middleware\ContentSuggestMetaRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\RateLimitMiddleware;
use PaginiumCMS\Http\Middleware\LoginRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\OtpResendRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\OtpStartRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\OtpVerifyRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\LocaleMiddleware;
use PaginiumCMS\Http\Middleware\MaintenanceModeMiddleware;
use PaginiumCMS\Http\Middleware\FirewallMiddleware;
use PaginiumCMS\Http\Middleware\CsrfMiddleware;
use PaginiumCMS\Http\Middleware\RequestLoggingMiddleware;

// ---------- PÔVODNÉ IMPORTY ----------
use PaginiumCMS\Modules\Security\Services\AuthenticationManager;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\CsrfProtectionManager;
use PaginiumCMS\Modules\Security\Services\PasswordPolicy;
use PaginiumCMS\Modules\Security\Services\SettingsBackedPasswordPolicy;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
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
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\FileDriver;
use PaginiumCMS\Core\Analytics\Middleware\AnalyticsMiddleware;
use PaginiumCMS\Http\Controllers\Auth\AuthController;
use PaginiumCMS\Http\Controllers\Auth\TwoFactorController;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Controllers\Admin\BackupController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Core\Backup\Services\BackupManager;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Services\BackupScheduler;
use PaginiumCMS\Core\Backup\Commands\RunBackupScheduleCommand;
use PaginiumCMS\Modules\Demo\Commands\RunDemoResetCommand;
use PaginiumCMS\Modules\Security\Commands\ClearLoginLockoutsCommand;
use PaginiumCMS\Modules\Demo\Services\DemoLoginGuard;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Demo\Services\DemoResetScheduler;
use PaginiumCMS\Modules\Demo\Services\DemoStorageService;
use PaginiumCMS\Core\Logging\Services\Logger;
use PaginiumCMS\Core\Logging\LogStoragePaths;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use PaginiumCMS\Core\Workflow\Services\OtpChallengeStore;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Core\Logging\Services\DebugEventLogger;
use PaginiumCMS\Http\Middleware\DebugRequestMiddleware;

// ---------- NAČÍTANIE UTF-8 ----------
require_once __DIR__ . '/utf8.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// ---------- .env (voliteľné, lokálny vývoj) ----------
$projectRoot = dirname(__DIR__, 2);
$backendRoot = dirname(__DIR__);
if (class_exists(\Dotenv\Dotenv::class)) {
    if (is_file($projectRoot . '/.env')) {
        \Dotenv\Dotenv::createUnsafeImmutable($projectRoot)->safeLoad();
    } elseif (is_file($backendRoot . '/.env')) {
        \Dotenv\Dotenv::createUnsafeImmutable($backendRoot)->safeLoad();
    }
}

require_once __DIR__ . '/timezone.php';

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
        return new FileValidator(DemoMode::resolveContentBasePath(__DIR__ . '/../storage/app'));
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

    \PaginiumCMS\Core\Cache\Drivers\MemoryDriver::class => function () {
        return new \PaginiumCMS\Core\Cache\Drivers\MemoryDriver();
    },

    \PaginiumCMS\Core\Cache\Drivers\ChainedDriver::class => function ($container) {
        return new \PaginiumCMS\Core\Cache\Drivers\ChainedDriver(
            $container->get(\PaginiumCMS\Core\Cache\Drivers\MemoryDriver::class),
            $container->get(FileDriver::class)
        );
    },

    CacheManager::class => function ($container) {
        return new CacheManager(
            $container->get(\PaginiumCMS\Core\Cache\Drivers\ChainedDriver::class),
            'paginium_',
            __DIR__ . '/../storage/cache/locks'
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
            LogStoragePaths::app()
        );
        return new Logger($writer, 'app');
    },

    // ============================================
    // 4. SECURITY LOGGER
    // ============================================

    SecurityLogger::class => function ($container) {
        return new SecurityLogger(
            $container->get(LoggerInterface::class),
            $container->get(LoginAttemptTracker::class),
            $container->get(IncidentNotifier::class),
            $container->get(SecurityAuditStore::class),
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

    LoginAttemptTracker::class => function ($container) {
        return new LoginAttemptTracker(
            $container->get(FileReaderInterface::class),
            $container->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class)
        );
    },

    SecurityAuditStore::class => function ($container) {
        return new SecurityAuditStore($container->get(FileReaderInterface::class));
    },

    OtpChallengeStore::class => function ($container) {
        return new OtpChallengeStore(
            $container->get(FileReaderInterface::class)
        );
    },

    OtpWorkflowService::class => function ($container) {
        return new OtpWorkflowService(
            $container->get(OtpChallengeStore::class),
            $container->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            $container->get(\PaginiumCMS\Core\Notification\NotificationService::class),
            $container->get(UserRepository::class),
            $container->get(\PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface::class),
            $container->get(\PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface::class),
            $container->get(\PaginiumCMS\Core\Versioning\Services\ContentVersioningService::class)
        );
    },

    // ============================================
    // 5. SECURITY MIDDLEWARE
    // ============================================

    SecurityMiddleware::class => function () {
        return new SecurityMiddleware([
            'hsts_max_age' => 31536000,
            'csp_default' => "default-src 'self'",
            // Bez 'unsafe-inline' pre skripty – Vite build používa len externé moduly.
            'csp_script' => "script-src 'self'",
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

    LocaleMiddleware::class => function ($container) {
        return new LocaleMiddleware(
            $container->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            $container->get(\PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry::class)
        );
    },

    MaintenanceModeMiddleware::class => function ($container) {
        return new MaintenanceModeMiddleware(
            $container->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            $container->get(AuthenticationInterface::class),
            $container->get(AuthorizationInterface::class)
        );
    },

    // OPRAVA (audit, nález #7): trustedProxies pridané - viď opravený
    // RateLimitMiddleware.php. Ak appka beží priamo (bez reverse proxy),
    // ponechajte trustedProxies prázdne pole - potom sa vždy použije
    // REMOTE_ADDR a X-Forwarded-For sa ignoruje (nedá sa sfalšovať).
    // Ak beží ZA nginx reverse proxy na tomto istom serveri, pridajte IP
    // toho proxy (zvyčajne 127.0.0.1, ak nginx a PHP-FPM bežia na tom
    // istom stroji/kontajneri).
    RateLimitMiddleware::class => function ($container) {
        $isTesting = (getenv('APP_ENV') === 'testing');
        return new RateLimitMiddleware(
            $container->get(CacheManager::class),
            maxRequests: $isTesting ? 100000 : (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 60),
            window: $isTesting ? 60 : (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 60),
            excludedPaths: ['/api/health', '/api/test', '/api/debug/client-event'],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            // Ak beží ZA nginx reverse proxy (LAN: .26 → PHP .20), pridajte IP nginx hosta.
    trustedProxies: array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    FirewallMiddleware::class => function ($container) {
        return new FirewallMiddleware(
            $container->get(\PaginiumCMS\Core\Security\Firewall\FirewallService::class),
            trustedProxies: array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    RequestLoggingMiddleware::class => function ($container) {
        return new RequestLoggingMiddleware(
            $container->get(\PaginiumCMS\Core\Logging\Services\AccessLogService::class),
            trustedProxies: array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    LoginRateLimitMiddleware::class => function ($container) {
        return new LoginRateLimitMiddleware(
            $container->get(CacheManager::class),
                                            // Ak beží ZA nginx reverse proxy (LAN: .26 → PHP .20), pridajte IP nginx hosta.
    trustedProxies: array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    OtpVerifyRateLimitMiddleware::class => function ($container) {
        return new OtpVerifyRateLimitMiddleware(
            $container->get(CacheManager::class),
            array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    OtpResendRateLimitMiddleware::class => function ($container) {
        return new OtpResendRateLimitMiddleware(
            $container->get(CacheManager::class),
            array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    OtpStartRateLimitMiddleware::class => function ($container) {
        return new OtpStartRateLimitMiddleware(
            $container->get(CacheManager::class),
            array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    ContentSuggestMetaRateLimitMiddleware::class => function ($container) {
        return new ContentSuggestMetaRateLimitMiddleware(
            $container->get(CacheManager::class),
            array_filter(explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? '127.0.0.1,::1,192.168.10.26')))
        );
    },

    // ============================================
    // 6. SESSION MANAGER (BEZPEČNÁ VERZIA)
    // ============================================

    SessionManager::class => function () {
        static $instance = null;
        if ($instance === null) {
            $instance = new \PaginiumCMS\Core\Security\SecureSessionManager();
        }

        return $instance;
    },

    // ============================================
    // 7. SECURITY CORE
    // ============================================

    PasswordPolicyInterface::class => function ($container) {
        return new SettingsBackedPasswordPolicy(
            $container->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class)
        );
    },

    // Šifrovanie tajomstiev „at-rest" (audit A1). Kľúč sa odvodí z APP_KEY;
    // ak nie je platný 32-bajtový kľúč, služba je vypnutá (plaintext) a
    // aktivuje sa nastavením reálneho APP_KEY – bez migračného skriptu.
    EncryptionService::class => function () {
        $appKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? null);

        return new EncryptionService(is_string($appKey) ? $appKey : null);
    },

    UserIndexService::class => function ($container) {
        return new UserIndexService(
            $container->get(FileReaderInterface::class),
            'data/index/users.json'
        );
    },

    UserRepository::class => function ($container) {
        return new UserRepository(
            $container->get(FileReaderInterface::class),
            $container->get(FileWriterInterface::class),
            'data/users',
            $container->get(EncryptionService::class),
            $container->get(UserIndexService::class)
        );
    },

    AuthenticationInterface::class => function ($container) {
        return new AuthenticationManager(
            $container->get(SessionManager::class),
                                         $container->get(PasswordPolicyInterface::class),
                                         $container->get(UserRepository::class)
        );
    },

    AuthorizationInterface::class => function ($container) {
        return new AuthorizationManager(
            $container->get(SecurityAuditStore::class),
            $container->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class)
        );
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
            $container->get(CsrfProtectionInterface::class),
            $container->get(PasswordPolicyInterface::class),
            $container->get(UserRepository::class),
            $container->get(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class),
            $container->get(\PaginiumCMS\Core\Notification\NotificationService::class),
            $container->get(LoginAttemptTracker::class),
            $container->get(SecurityLogger::class),
            $container->get(OtpWorkflowService::class),
            $container->get(JsonResponder::class),
            $container->get(DemoLoginGuard::class)
        );
    },

    TwoFactorController::class => function ($container) {
        return new TwoFactorController(
            $container->get(TwoFactorInterface::class),
            $container->get(UserRepository::class),
            $container->get(AuthenticationInterface::class),
            $container->get(JsonResponder::class)
        );
    },

    AuthMiddleware::class => function ($container) {
        return new AuthMiddleware(
            $container->get(AuthenticationInterface::class)
        );
    },

    TwoFactorMiddleware::class => function ($container) {
        return new TwoFactorMiddleware(
            $container->get(TwoFactorInterface::class)
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
                                 DemoMode::resolveContentBasePath(__DIR__ . '/../storage/app')
        );
    },

    BackupController::class => function ($container) {
        return new BackupController(
            $container->get(BackupInterface::class),
            $container->get(JsonResponder::class)
        );
    },

    BackupScheduler::class => function ($container) {
        return new BackupScheduler($container->get(BackupInterface::class));
    },

    RunBackupScheduleCommand::class => function ($container) {
        return new RunBackupScheduleCommand($container->get(BackupScheduler::class));
    },

    DemoResetScheduler::class => function ($container) {
        return new DemoResetScheduler(
            $container->get(DemoMode::class),
            $container->get(DemoStorageService::class)
        );
    },

    RunDemoResetCommand::class => function ($container) {
        return new RunDemoResetCommand($container->get(DemoResetScheduler::class));
    },

    ClearLoginLockoutsCommand::class => function ($container) {
        return new ClearLoginLockoutsCommand(
            $container->get(LoginAttemptTracker::class),
            $container->get(CacheManager::class)
        );
    },

]);

// ============================================
// 10. NAČÍTANIE LOGGING SLUŽIEB (Core moduly majú vlastný Config/services.php)
// ============================================

$loggingServices = require __DIR__ . '/../app/Core/Logging/Config/services.php';
if (is_array($loggingServices)) {
    $containerBuilder->addDefinitions($loggingServices);
} elseif (is_callable($loggingServices)) {
    $definitions = $loggingServices();
    if (is_array($definitions)) {
        $containerBuilder->addDefinitions($definitions);
    }
}

$monitoringServices = require __DIR__ . '/../app/Core/Monitoring/Config/services.php';
if (is_array($monitoringServices)) {
    $containerBuilder->addDefinitions($monitoringServices);
}

$schedulerServices = require __DIR__ . '/../app/Core/Scheduler/Config/services.php';
if (is_array($schedulerServices)) {
    $containerBuilder->addDefinitions($schedulerServices);
}

// ============================================
// 10b. NAČÍTANIE DI VÄZIEB PRE HTTP VRSTVU (nové API endpointy, oddelené
// od jadra - Content, Media, ...). PRIDANÉ pri oprave auditu 12.7.2026,
// nález #4: tieto väzby existovali z predchádzajúcich session, ale nikdy
// neboli sem zapojené, takže ContentController/MediaController sa nedali
// vôbec vytvoriť cez kontajner.
// ============================================

$httpServices = require __DIR__ . '/../app/Http/Config/services.php';
if (is_array($httpServices)) {
    $containerBuilder->addDefinitions($httpServices);
}

$debugServices = require __DIR__ . '/../app/Http/Config/debug.php';
if (is_array($debugServices)) {
    $containerBuilder->addDefinitions($debugServices);
}

// ============================================
// 11. VYTVORENIE KONTEJNERA A APLIKÁCIE
// ============================================

$container = $containerBuilder->build();

if (DebugEventLogger::isEnabled()) {
    DebugEventLogger::log('backend', 'di.container.built', [
        'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
    ]);
}

AppFactory::setContainer($container);
$app = AppFactory::create();

// ============================================
// 12. CORS (BEZPEČNOSTNÁ VERZIA)
// ============================================

$corsAllowedOrigins = [
    'http://localhost:3025',
    'http://localhost:5173',
    'http://localhost:4173',
];

$appUrl = getenv('APP_URL');
if (is_string($appUrl) && $appUrl !== '') {
    $corsAllowedOrigins[] = rtrim($appUrl, '/');
}

$corsExtra = getenv('CORS_ALLOWED_ORIGINS') ?: ($_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
if (is_string($corsExtra) && $corsExtra !== '') {
    foreach (explode(',', $corsExtra) as $origin) {
        $origin = rtrim(trim($origin), '/');
        if ($origin !== '') {
            $corsAllowedOrigins[] = $origin;
        }
    }
}

foreach ([
    getenv('DEMO_PUBLIC_URL') ?: ($_ENV['DEMO_PUBLIC_URL'] ?? null),
    getenv('VITE_PUBLIC_URL') ?: ($_ENV['VITE_PUBLIC_URL'] ?? null),
] as $publicUrl) {
    if (is_string($publicUrl) && $publicUrl !== '') {
        $corsAllowedOrigins[] = rtrim($publicUrl, '/');
    }
}

$corsAllowedOrigins = array_values(array_unique($corsAllowedOrigins));

$appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
$corsOriginServer = is_string($appUrl) && $appUrl !== '' ? rtrim($appUrl, '/') : null;

// Dev/LAN: allow private network + any localhost port (Vite :3025, nginx :8081, …)
if ($appEnv !== 'production') {
    $corsAllowedOrigins = array_values(array_unique(array_merge($corsAllowedOrigins, [
        'http://localhost:*',
        'http://127.0.0.1:*',
        'http://192.168.*',
        'http://10.*',
        'http://172.*',
    ])));
}

$corsOptions = [
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
];

if ($corsOriginServer !== null) {
    $corsOptions['origin.server'] = $corsOriginServer;
}

$app->add(new SameOriginCorsMiddleware($corsOptions));

// ============================================
// 13. BEZPEČNOSTNÉ MIDDLEWARE (GLOBÁLNE)
// ============================================

$app->add($container->get(SecurityMiddleware::class));
$app->add($container->get(MaintenanceModeMiddleware::class));
$app->add($container->get(LocaleMiddleware::class));
$app->add($container->get(FirewallMiddleware::class));
// CSRF (audit S3 / ISS-012): vynucuje synchronizer-token na mutujúcich
// requestoch. V testing prostredí je no-op (viď CsrfMiddleware).
$app->add(new CsrfMiddleware($container->get(CsrfProtectionInterface::class)));
$app->add($container->get(RateLimitMiddleware::class));
$app->add($container->get(AnalyticsMiddleware::class));

// ============================================
// 14. ROUTY
// ============================================

// ---------- AUTH ROUTY ----------
$app->group('/api/auth', function (RouteCollectorProxy $group) use ($container) {
    $authController = $container->get(AuthController::class);
    $twoFactorController = $container->get(TwoFactorController::class);

    $group->post('/register', [$authController, 'register'])
        ->add($container->get(OtpStartRateLimitMiddleware::class));
    $group->post('/register/verify-otp', [$authController, 'verifyRegisterOtp'])
        ->add($container->get(OtpVerifyRateLimitMiddleware::class));
    $group->post('/register/resend-otp', [$authController, 'resendRegisterOtp'])
        ->add($container->get(OtpResendRateLimitMiddleware::class));

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
    })->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
});

// ---------- BACKUP ROUTY ----------
$app->group('/api/admin', function (RouteCollectorProxy $group) use ($container) {
    $backupController = $container->get(BackupController::class);

    $group->get('/backups', [$backupController, 'listBackups']);
    $group->post('/backups', [$backupController, 'createBackup']);
    $group->post('/backups/import', [$backupController, 'importBackup']);
    $group->post('/backups/bulk-delete', [$backupController, 'bulkDeleteBackups']);
    $group->post('/backups/bulk-restore', [$backupController, 'bulkRestoreBackups']);
    $group->get('/backups/{id}/download', [$backupController, 'downloadBackup']);
    $group->get('/backups/{id}/verify', [$backupController, 'verifyBackup']);
    $group->post('/backups/{id}/restore', [$backupController, 'restoreBackup']);
    $group->delete('/backups/{id}', [$backupController, 'deleteBackup']);
    $group->get('/backups/schedule', [$backupController, 'getSchedule']);
    $group->post('/backups/schedule', [$backupController, 'scheduleBackup']);
})->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
    ->add($container->get(TwoFactorMiddleware::class))
    ->add($container->get(AuthMiddleware::class));

// ---------- HEALTH CHECK ----------
$app->get('/api/health', function ($request, $response) use ($container) {
    $json = $container->get(JsonResponder::class);

    return $json->success($response, [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '2.0.9',
        'php_version' => PHP_VERSION,
        'environment' => getenv('APP_ENV') ?: 'development',
    ]);
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

$routeFiles = glob(__DIR__ . '/../app/Http/Routes/*.php');
if ($routeFiles === false) {
    $routeFiles = [];
}

foreach ($routeFiles as $routeFile) {
    if (basename($routeFile) === 'auth.php') {
        // Duplicitná/nefunkčná kópia auth routes - viď poznámka vyššie.
        // Auth je už plne funkčný v bloku "AUTH ROUTY" vyššie v tomto súbore.
        continue;
    }
    $register = require $routeFile;
    if (is_callable($register)) {
        $register($app);
        if (DebugEventLogger::isEnabled()) {
            DebugEventLogger::log('backend', 'routes.module.loaded', [
                'module' => basename($routeFile, '.php'),
            ]);
        }
    }
}

// ---------- ENABLED EXTENSION ROUTES (It.15) ----------
$pluginManager = $container->get(\PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface::class);
$pluginManager->bootEnabledExtensions();

$enabledExtensionIds = array_fill_keys($pluginManager->getEnabledIds(), true);
$extensionRouteFiles = glob(__DIR__ . '/../app/Http/Routes/extensions/*.php');
if ($extensionRouteFiles === false) {
    $extensionRouteFiles = [];
}

foreach ($extensionRouteFiles as $routeFile) {
    $extensionId = basename($routeFile, '.php');
    if (!isset($enabledExtensionIds[$extensionId])) {
        continue;
    }

    $register = require $routeFile;
    if (is_callable($register)) {
        $register($app);
        if (DebugEventLogger::isEnabled()) {
            DebugEventLogger::log('backend', 'routes.extension.loaded', [
                'extension' => $extensionId,
            ]);
        }
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

    $response->getBody()->write((string) json_encode([
        'success' => false,
        'error' => 'Endpoint nenájdený',
        'path' => $path,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $response
    ->withStatus(404)
    ->withHeader('Content-Type', 'application/json');
});

// ============================================
// 16. JEDNOTNÝ ERROR HANDLER (Iterácia 4)
//
// Neošetrené výnimky sa prevedú na jednotný JSON obal
// { success:false, error, ... }. ValidationException → 422 (+ errors),
// Slim HttpException → jeho status, ostatné → 500.
// Pridané tu (pred záverečným CORS obalom), takže CORS obal ostáva
// najvrchnejší a error odpovede tiež dostanú CORS hlavičky.
// display_error_details je zapnuté mimo produkcie.
// ============================================

$displayErrorDetails = getenv('APP_ENV') !== 'production';
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);
$errorMiddleware->setDefaultErrorHandler(
    new \PaginiumCMS\Http\Support\ApiErrorHandler($app->getResponseFactory())
);

// Pridanie CORS Middleware IBA pre lokálny vývoj.
// Audit 2026-07-22: táto natvrdo zadaná closure predtým VŽDY nastavovala
// Allow-Origin: http://localhost:5173 + credentials:true, aj v produkcii, kde
// kolidovala s Tuupola CorsMiddleware. Gatujeme ju len na ne-produkčné prostredie;
// v produkcii CORS rieši výhradne konfigurovaný CorsMiddleware vyššie.
$appEnvCors = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'));
if ($appEnvCors !== 'production') {
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);

        // Tieto hlavičky dovolia Reactu bezpečne čítať API odpovede
        return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-CSRF-Token')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true'); // Dôležité pre cookies/session
    });
}

// Ošetrenie predbežných OPTIONS požiadaviek, ktoré prehliadač automaticky posiela
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

if (DebugEventLogger::isEnabled()) {
    $app->add(new DebugRequestMiddleware());
}

$app->add($container->get(RequestLoggingMiddleware::class));

if (DebugEventLogger::isEnabled()) {
    $routeFiles = glob(__DIR__ . '/../app/Http/Routes/*.php') ?: [];
    DebugEventLogger::log('backend', 'bootstrap.complete', [
        'php_version' => PHP_VERSION,
        'app_env' => getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'),
        'route_modules' => count($routeFiles),
        'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'sapi' => PHP_SAPI,
    ]);
}

$container->get(DemoStorageService::class)->ensureSeeded();

return $app;
