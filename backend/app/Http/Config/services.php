<?php

declare(strict_types=1);

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Middleware\AnalyticsMiddleware;
use PaginiumCMS\Core\Analytics\Services\AnalyticsManager;
use PaginiumCMS\Core\Analytics\Services\GeoIPService;
use PaginiumCMS\Core\Analytics\Services\RealtimeTracker;
use PaginiumCMS\Core\Analytics\Services\Reporter;
use PaginiumCMS\Core\Analytics\Services\Tracker;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Health\Services\Checkers\CacheChecker;
use PaginiumCMS\Core\Health\Services\Checkers\SecurityChecker;
use PaginiumCMS\Core\Health\Services\Checkers\StorageChecker;
use PaginiumCMS\Core\Health\Services\Checkers\SystemChecker;
use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Notification\Services\NotificationFactory;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\CodeEditor\Services\CodeEditorLogger;
use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodeEditor\Services\FileBackup;
use PaginiumCMS\Core\CodeEditor\Services\DiffGenerator;
use PaginiumCMS\Core\Config\ConfigManager;
use PaginiumCMS\Core\Developer\DeveloperMode;
use PaginiumCMS\Core\Developer\DeveloperModeGate;
use PaginiumCMS\Core\Developer\DevTokenGenerator;
use PaginiumCMS\Core\Developer\DevTokenRegistry;
use PaginiumCMS\Core\Developer\Services\DeveloperLogger;
use PaginiumCMS\Core\Event\EventDispatcher;
use PaginiumCMS\Core\GitHub\Services\GitHubService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FrontMatterParserInterface;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownParserInterface;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Conflict\Services\ConflictLogger;
use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Core\Drafts\Services\DraftManager;
use PaginiumCMS\Core\FlatFile\Services\ContentRepository;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\JsonContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentStorage;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownParser;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Locking\Services\LockManager;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Http\Controllers\Admin\AnalyticsController;
use PaginiumCMS\Http\Controllers\Admin\AuditTrailController;
use PaginiumCMS\Http\Controllers\Admin\DashboardController;
use PaginiumCMS\Http\Controllers\Admin\HealthController;
use PaginiumCMS\Http\Controllers\Admin\GitHubController;
use PaginiumCMS\Http\Controllers\Admin\MessageController;
use PaginiumCMS\Http\Controllers\Admin\NotificationController;
use PaginiumCMS\Http\Controllers\Admin\CodeEditorController;
use PaginiumCMS\Http\Controllers\Admin\DeveloperController;
use PaginiumCMS\Http\Controllers\Admin\GatedCodeEditorController;
use PaginiumCMS\Http\Controllers\Admin\SettingsController;
use PaginiumCMS\Http\Controllers\Admin\VersionController;
use PaginiumCMS\Http\Controllers\Admin\ConflictController;
use PaginiumCMS\Http\Controllers\Admin\UserController;
use PaginiumCMS\Http\Controllers\Validation\ValidationController;
use PaginiumCMS\Http\Controllers\Comments\CommentsController;
use PaginiumCMS\Http\Controllers\Contact\ContactController;
use PaginiumCMS\Http\Controllers\Navigation\NavigationController;
use PaginiumCMS\Http\Controllers\Content\ContentController;
use PaginiumCMS\Http\Controllers\Content\DraftController;
use PaginiumCMS\Http\Controllers\Content\SearchController;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Controllers\Locking\LockController;
use PaginiumCMS\Http\Controllers\Media\MediaController;
use PaginiumCMS\Http\Middleware\DeveloperModeMiddleware;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;
use PaginiumCMS\Modules\Navigation\Services\NavigationRepository;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;

use function DI\create;
use function DI\get;

return [
    // FlatFile content stack
    FrontMatterParserInterface::class => create(FrontMatterParser::class),
    MarkdownContentParserInterface::class => create(MarkdownContentParser::class),
    MarkdownParserInterface::class => create(MarkdownParser::class)
        ->constructor(
            get(FrontMatterParserInterface::class),
            get(MarkdownContentParserInterface::class)
        ),
    MarkdownContentStorage::class => create(MarkdownContentStorage::class)
        ->constructor(get(MarkdownParserInterface::class)),
    JsonContentStorage::class => create(JsonContentStorage::class)
        ->constructor(get(MarkdownContentParserInterface::class)),
    ContentIndexService::class => create(ContentIndexService::class)
        ->constructor(
            get(FileReaderInterface::class),
            'data/index/content.json'
        ),
    JsonResponder::class => create(JsonResponder::class),
    ContentRepositoryInterface::class => create(ContentRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(ContentIndexService::class),
            get(MarkdownContentStorage::class),
            get(JsonContentStorage::class),
            get(SettingsRepositoryInterface::class)
        ),

    // === Blok: Nastavenia + validácia (Iterácia 4) ===
    // Zdieľaný validator (bezstavový) – používa ho SettingsRepository aj ďalšie moduly.
    Validator::class => create(Validator::class),

    // Flat-file úložisko nastavení (data/settings.json) – ukladá iba odchýlky od predvolieb.
    SettingsRepositoryInterface::class => create(SettingsRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(Validator::class),
            'data/settings.json'
        ),
    SettingsController::class => create(SettingsController::class)
        ->constructor(get(SettingsRepositoryInterface::class)),

    ValidationController::class => create(ValidationController::class),

    UserController::class => create(UserController::class)
        ->constructor(
            get(UserRepository::class),
            get(Validator::class),
            get(PasswordPolicyInterface::class)
        ),

    // Revízny odtlačok obsahu (optimistické zamykanie / detekcia konfliktov – Iterácia 2)
    ContentRevision::class => create(ContentRevision::class),

    // Log konfliktov obsahu (Iterácia 3) – flat-file data/conflicts.json
    ConflictLoggerInterface::class => create(ConflictLogger::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/conflicts.json',
            200
        ),
    ConflictController::class => create(ConflictController::class)
        ->constructor(get(ConflictLoggerInterface::class)),

    // Auto-save koncepty (Iterácia 2) – oddelené flat-file úložisko data/drafts/
    DraftManagerInterface::class => create(DraftManager::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/drafts'
        ),
    DraftController::class => create(DraftController::class)
        ->constructor(get(DraftManagerInterface::class)),

    // Content cache (ChainedDriver via bootstrap CacheManager)
    ContentCacheService::class => create(ContentCacheService::class)
        ->constructor(get(CacheManager::class)),

    // Media module
    MediaRepositoryInterface::class => create(MediaRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),

    NavigationRepositoryInterface::class => create(NavigationRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    NavigationController::class => create(NavigationController::class)
        ->constructor(get(NavigationRepositoryInterface::class)),

    CommentsRepositoryInterface::class => create(CommentsRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    CommentsController::class => create(CommentsController::class)
        ->constructor(
            get(CommentsRepositoryInterface::class),
            get(SettingsRepositoryInterface::class),
            get(Validator::class)
        ),

    MessageRepositoryInterface::class => create(MessageRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    ContactController::class => create(ContactController::class)
        ->constructor(
            get(MessageRepositoryInterface::class),
            get(Validator::class)
        ),
    MessageController::class => create(MessageController::class)
        ->constructor(get(MessageRepositoryInterface::class)),

    GitHubService::class => function ($container) {
        return new GitHubService(
            $container->get(FileReaderInterface::class),
            $container->get(FileWriterInterface::class),
            [
                'token' => getenv('GITHUB_TOKEN') ?: ($_ENV['GITHUB_TOKEN'] ?? ''),
                'repo' => getenv('GITHUB_REPO') ?: ($_ENV['GITHUB_REPO'] ?? ''),
                'branch' => getenv('GITHUB_BRANCH') ?: ($_ENV['GITHUB_BRANCH'] ?? 'main'),
                'enabled' => filter_var(getenv('GITHUB_ENABLED') ?: ($_ENV['GITHUB_ENABLED'] ?? 'false'), FILTER_VALIDATE_BOOLEAN),
                'auto_sync' => filter_var(getenv('GITHUB_AUTO_SYNC') ?: ($_ENV['GITHUB_AUTO_SYNC'] ?? 'false'), FILTER_VALIDATE_BOOLEAN),
                'content_path' => getenv('GITHUB_CONTENT_PATH') ?: ($_ENV['GITHUB_CONTENT_PATH'] ?? 'content'),
            ]
        );
    },
    GitHubController::class => create(GitHubController::class)
        ->constructor(get(GitHubService::class)),

    // === Blok: Systém zamykania obsahu (Iterácia 1) ===
    // Flat-file manažér zámkov (data/locks.json), TTL 300 s = auto-release po 5 min.
    LockManagerInterface::class => create(LockManager::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(LoggerInterface::class),
            'data/locks.json',
            300
        ),
    LockController::class => create(LockController::class)
        ->constructor(get(LockManagerInterface::class)),

    // Developer Mode gate + offline tokens
    DevTokenRegistry::class => create(DevTokenRegistry::class),
    DevTokenGenerator::class => function () {
        $secret = (string) (getenv('DEV_UNLOCK_SECRET') ?: ($_ENV['DEV_UNLOCK_SECRET'] ?? ''));

        if ($secret === '') {
            $appEnv = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'));
            $appDebug = filter_var(
                getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'true'),
                FILTER_VALIDATE_BOOLEAN
            );
            $localEnvs = ['testing', 'test', 'development', 'local'];

            if (in_array($appEnv, $localEnvs, true) || $appDebug) {
                $secret = 'paginium-local-dev-unlock-secret';
            }
        }

        return new DevTokenGenerator($secret);
    },
    DeveloperModeGate::class => create(DeveloperModeGate::class)
        ->constructor(
            get(DevTokenGenerator::class),
            get(DevTokenRegistry::class)
        ),
    DeveloperLogger::class => create(DeveloperLogger::class),
    DeveloperModeMiddleware::class => create(DeveloperModeMiddleware::class)
        ->constructor(get(DeveloperModeGate::class)),

    // HTTP controllers
    ContentController::class => create(ContentController::class)
        ->constructor(
            get(ContentRepositoryInterface::class),
            get(ContentVersioningService::class),
            get(ContentCacheService::class),
            get(ContentRevision::class),
            get(ConflictLoggerInterface::class),
            get(JsonResponder::class),
            get(SettingsRepositoryInterface::class),
            get(AuthenticationInterface::class)
        ),
    SearchController::class => create(SearchController::class)
        ->constructor(
            get(ContentIndexService::class),
            get(ContentRepositoryInterface::class),
            get(JsonResponder::class)
        ),
    MediaController::class => create(MediaController::class)
        ->constructor(get(MediaRepositoryInterface::class)),

    // Code editor / versioning / audit (auto-discovered admin routes)
    ConfigManager::class => create(ConfigManager::class),
    EventDispatcher::class => create(EventDispatcher::class),
    DeveloperMode::class => create(DeveloperMode::class)
        ->constructor(
            get(ConfigManager::class),
            get(EventDispatcher::class),
            get(DeveloperModeGate::class),
            get(DeveloperLogger::class)
        ),
    CodeEditorLogger::class => create(CodeEditorLogger::class)
        ->constructor(get(LoggerInterface::class), get(DeveloperMode::class)),
    SyntaxChecker::class => create(SyntaxChecker::class),
    SecurityScanner::class => create(SecurityScanner::class),
    CodePolicyEngineInterface::class => create(CodePolicyEngine::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(SyntaxChecker::class),
            get(SecurityScanner::class)
        ),
    FileBackup::class => create(FileBackup::class),
    CodeEditorManager::class => create(CodeEditorManager::class)
        ->constructor(
            get(SyntaxChecker::class),
            get(FileBackup::class),
            get(CodeEditorLogger::class),
            get(CodePolicyEngineInterface::class)
        ),
    DiffGenerator::class => create(DiffGenerator::class),
    EnhancedVersionManager::class => create(EnhancedVersionManager::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(DiffGenerator::class),
            get(CodeEditorLogger::class),
            'data/versions',
            50
        ),

    // === Blok: Notifikácie + analytika (Iterácia 6) ===
    GeoIPService::class => create(GeoIPService::class),
    TrackerInterface::class => create(Tracker::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(GeoIPService::class),
            'data/analytics'
        ),
    ReporterInterface::class => create(Reporter::class)
        ->constructor(get(TrackerInterface::class)),
    NotificationService::class => function ($container) {
        return NotificationFactory::create($container->get(SettingsRepositoryInterface::class));
    },
    IncidentNotifier::class => create(IncidentNotifier::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(NotificationService::class)
        ),
    AnalyticsManager::class => create(AnalyticsManager::class)
        ->constructor(
            get(TrackerInterface::class),
            get(ReporterInterface::class),
            get(SettingsRepositoryInterface::class),
            get(IncidentNotifier::class)
        ),
    AnalyticsMiddleware::class => create(AnalyticsMiddleware::class)
        ->constructor(get(AnalyticsManager::class)),
    RealtimeTracker::class => create(RealtimeTracker::class)
        ->constructor(get(TrackerInterface::class)),
    AnalyticsController::class => create(AnalyticsController::class)
        ->constructor(
            get(ReporterInterface::class),
            get(RealtimeTracker::class)
        ),
    DashboardController::class => create(DashboardController::class)
        ->constructor(
            get(LockManagerInterface::class),
            get(ConflictLoggerInterface::class),
            get(HealthCheckManager::class),
            get(ReporterInterface::class),
            get(RealtimeTracker::class)
        ),

    // === Blok: Health checks (Iteration 7) ===
    SystemChecker::class => create(SystemChecker::class),
    StorageChecker::class => create(StorageChecker::class)
        ->constructor(dirname(__DIR__, 3) . '/storage'),
    CacheChecker::class => create(CacheChecker::class)
        ->constructor(get(CacheManager::class)),
    SecurityChecker::class => create(SecurityChecker::class),
    HealthCheckManager::class => create(HealthCheckManager::class)
        ->method('addChecks', [
            get(SystemChecker::class),
            get(StorageChecker::class),
            get(CacheChecker::class),
            get(SecurityChecker::class),
        ]),
    HealthController::class => create(HealthController::class)
        ->constructor(get(HealthCheckManager::class)),

    NotificationController::class => create(NotificationController::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(NotificationService::class),
            get(ReporterInterface::class)
        ),

    AuditTrailService::class => create(AuditTrailService::class)
        ->constructor(
            get(LoggerInterface::class),
            get(EnhancedVersionManager::class),
            get(UserRepository::class),
            get(IncidentNotifier::class)
        ),
    ContentVersioningService::class => create(ContentVersioningService::class)
        ->constructor(
            get(AuditTrailService::class),
            get(EnhancedVersionManager::class),
            get(ContentRepositoryInterface::class),
            get(FrontMatterParserInterface::class),
            get(ContentCacheService::class)
        ),
    CodeEditorController::class => create(GatedCodeEditorController::class)
        ->constructor(
            get(CodeEditorManager::class),
            get(DeveloperModeGate::class)
        ),
    VersionController::class => create(VersionController::class)
        ->constructor(
            get(EnhancedVersionManager::class),
            get(ContentVersioningService::class)
        ),
    AuditTrailController::class => create(AuditTrailController::class)
        ->constructor(get(AuditTrailService::class)),
    DeveloperController::class => create(DeveloperController::class)
        ->constructor(
            get(DeveloperModeGate::class),
            get(DeveloperMode::class),
            get(DeveloperLogger::class),
            get(TwoFactorInterface::class)
        ),
];
