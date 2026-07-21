<?php

declare(strict_types=1);

use PaginiumCMS\Core\Admin\Services\AdminCountsService;
use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Blueprint\Services\BlueprintRepository;
use PaginiumCMS\Core\Blueprint\Services\DynamicValidator;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
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
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportScheduler;
use PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler;
use PaginiumCMS\Core\Monitoring\Services\SchedulerStateStore;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Notification\Services\NotificationFactory;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Cache\Commands\PurgeContentCacheCommand;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PaginiumCMS\Core\FlatFile\Commands\ContentDiagnoseCommand;
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
use PaginiumCMS\Core\I18n\Contracts\TranslationFileManagerInterface;
use PaginiumCMS\Core\I18n\Services\TranslationFileManager;
use PaginiumCMS\Core\I18n\Services\TranslationPolicyValidator;
use PaginiumCMS\Core\GitHub\Services\GitHubService;
use PaginiumCMS\Core\Hook\HookManager;
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
use PaginiumCMS\Core\Editor\Services\EditorContentValidator;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\FlatFile\Services\ContentRepository;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\JsonContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentStorage;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Core\Feeds\Services\FeedGenerator;
use PaginiumCMS\Core\Feeds\Services\RobotsTxtGenerator;
use PaginiumCMS\Core\Feeds\Services\SitemapGenerator;
use PaginiumCMS\Core\Seo\Services\SeoMetaBuilder;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use PaginiumCMS\Core\Security\Firewall\FirewallBanStore;
use PaginiumCMS\Core\Security\Firewall\FirewallIncidentLogger;
use PaginiumCMS\Core\Security\Firewall\FirewallScenarioRegistry;
use PaginiumCMS\Core\Security\Firewall\FirewallScanner;
use PaginiumCMS\Core\Security\Firewall\FirewallService;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Core\Logging\Services\AccessLogService;
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownParser;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Locking\Services\LockManager;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Search\Services\AdvancedSearchService;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Http\Controllers\Admin\CacheController;
use PaginiumCMS\Http\Controllers\Admin\AnalyticsController;
use PaginiumCMS\Http\Controllers\Admin\AuditTrailController;
use PaginiumCMS\Http\Controllers\Admin\DashboardController;
use PaginiumCMS\Http\Controllers\Admin\HealthController;
use PaginiumCMS\Http\Controllers\Admin\GitHubController;
use PaginiumCMS\Http\Controllers\Admin\MessageController;
use PaginiumCMS\Http\Controllers\Admin\NotificationController;
use PaginiumCMS\Http\Controllers\Admin\CodeEditorController;
use PaginiumCMS\Http\Controllers\Admin\DemoController;
use PaginiumCMS\Http\Controllers\Admin\DeveloperController;
use PaginiumCMS\Http\Controllers\Admin\GatedCodeEditorController;
use PaginiumCMS\Http\Controllers\Admin\ExtensionsController;
use PaginiumCMS\Http\Controllers\Admin\BlueprintController;
use PaginiumCMS\Http\Controllers\Admin\AclController;
use PaginiumCMS\Http\Controllers\Admin\SecurityAuditController;
use PaginiumCMS\Http\Controllers\Admin\SettingsController;
use PaginiumCMS\Http\Controllers\Admin\TranslationController;
use PaginiumCMS\Http\Controllers\Auth\SsoController;
use PaginiumCMS\Http\Controllers\Admin\TrashController;
use PaginiumCMS\Http\Controllers\Admin\FirewallController;
use PaginiumCMS\Http\Controllers\Admin\LogController;
use PaginiumCMS\Http\Controllers\Feeds\FeedController;
use PaginiumCMS\Http\Controllers\Seo\SeoController;
use PaginiumCMS\Http\Controllers\Admin\VersionController;
use PaginiumCMS\Http\Controllers\Admin\WorkflowController;
use PaginiumCMS\Http\Controllers\Admin\ConflictController;
use PaginiumCMS\Http\Controllers\Admin\CountsController;
use PaginiumCMS\Http\Controllers\Admin\UserController;
use PaginiumCMS\Http\Controllers\Validation\ValidationController;
use PaginiumCMS\Http\Controllers\Comments\CommentsController;
use PaginiumCMS\Http\Controllers\Contact\ContactController;
use PaginiumCMS\Http\Controllers\Navigation\NavigationController;
use PaginiumCMS\Http\Controllers\Content\ContentController;
use PaginiumCMS\Http\Controllers\Content\DraftController;
use PaginiumCMS\Http\Controllers\Content\SearchController;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PaginiumCMS\Http\Extensions\Services\PluginImporter;
use PaginiumCMS\Http\Extensions\Services\PluginManager;
use PaginiumCMS\Http\Extensions\Services\PluginPolicyScanner;
use PaginiumCMS\Http\Extensions\Services\PluginRegistry;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Controllers\Locking\LockController;
use PaginiumCMS\Http\Controllers\Media\MediaController;
use PaginiumCMS\Http\Middleware\DeveloperModeMiddleware;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Services\CommentPolicyResolver;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;
use PaginiumCMS\Modules\Navigation\Services\NavigationRepository;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PaginiumCMS\Modules\Media\Services\StockImageCatalog;
use PaginiumCMS\Modules\Media\Services\StockImageImporter;
use PaginiumCMS\Modules\Demo\Contracts\DemoDataProviderInterface;
use PaginiumCMS\Modules\Demo\Commands\RunDemoResetCommand;
use PaginiumCMS\Modules\Demo\Services\DemoDataProvider;
use PaginiumCMS\Modules\Demo\Services\DemoLoginGuard;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Demo\Services\DemoResetScheduler;
use PaginiumCMS\Modules\Demo\Services\DemoStorageService;
use PaginiumCMS\Modules\Security\Services\AclRepository;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\OAuthSsoService;
use PaginiumCMS\Modules\Security\Services\PathAclService;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;

use function DI\create;
use function DI\get;

return [
    // FlatFile content stack
    FrontMatterParserInterface::class => create(FrontMatterParser::class),
    MarkdownContentParserInterface::class => create(MarkdownContentParser::class),
    TiptapHtmlRenderer::class => create(TiptapHtmlRenderer::class),
    ContentBodyRenderer::class => create(ContentBodyRenderer::class)
        ->constructor(
            get(MarkdownContentParserInterface::class),
            get(TiptapHtmlRenderer::class)
        ),
    MarkdownParserInterface::class => create(MarkdownParser::class)
        ->constructor(
            get(FrontMatterParserInterface::class),
            get(ContentBodyRenderer::class)
        ),
    MarkdownContentStorage::class => create(MarkdownContentStorage::class)
        ->constructor(get(MarkdownParserInterface::class)),
    JsonContentStorage::class => create(JsonContentStorage::class)
        ->constructor(get(ContentBodyRenderer::class)),
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

    // Flat-file úložisko nastavení – produkcia: data/settings.json; PHPUnit/HTTP testy: data/settings.testing.json
    SettingsRepositoryInterface::class => function ($container) {
        $appEnv = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'));
        $settingsFile = in_array($appEnv, ['testing', 'test'], true)
            ? 'data/settings.testing.json'
            : 'data/settings.json';

        return new SettingsRepository(
            $container->get(FileReaderInterface::class),
            $container->get(FileWriterInterface::class),
            $container->get(Validator::class),
            $settingsFile
        );
    },
    SettingsController::class => create(SettingsController::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(JsonResponder::class),
            get(SecurityLogger::class),
            get(DemoMode::class),
            get(EditorProfileService::class)
        ),

    TranslationFileManagerInterface::class => create(TranslationFileManager::class)
        ->constructor(
            get(TranslationPolicyValidator::class),
            get(FileBackup::class)
        ),
    TranslationPolicyValidator::class => create(TranslationPolicyValidator::class)
        ->constructor(get(SyntaxChecker::class)),
    TranslationController::class => create(TranslationController::class)
        ->constructor(
            get(TranslationFileManagerInterface::class),
            get(JsonResponder::class)
        ),

    WorkflowController::class => create(WorkflowController::class)
        ->constructor(
            get(OtpWorkflowService::class),
            get(JsonResponder::class)
        ),

    AdminCountsService::class => create(AdminCountsService::class)
        ->constructor(
            get(ContentRepositoryInterface::class),
            get(MediaRepositoryInterface::class),
            get(CommentsRepositoryInterface::class),
            get(MessageRepositoryInterface::class),
            get(BackupInterface::class),
            get(TrashService::class),
            get(UserRepository::class),
            get(FirewallService::class)
        ),

    CountsController::class => create(CountsController::class)
        ->constructor(
            get(AdminCountsService::class),
            get(JsonResponder::class)
        ),

    ValidationController::class => create(ValidationController::class)
        ->constructor(get(JsonResponder::class)),

    UserController::class => create(UserController::class)
        ->constructor(
            get(UserRepository::class),
            get(SettingsRepositoryInterface::class),
            get(Validator::class),
            get(PasswordPolicyInterface::class),
            get(JsonResponder::class)
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
        ->constructor(
            get(ConflictLoggerInterface::class),
            get(JsonResponder::class)
        ),

    // Auto-save koncepty (Iterácia 2) – oddelené flat-file úložisko data/drafts/
    DraftManagerInterface::class => create(DraftManager::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/drafts'
        ),
    DraftController::class => create(DraftController::class)
        ->constructor(
            get(DraftManagerInterface::class),
            get(JsonResponder::class)
        ),

    // Content cache (ChainedDriver via bootstrap CacheManager)
    ContentCacheService::class => create(ContentCacheService::class)
        ->constructor(get(CacheManager::class)),

    CacheAdminService::class => create(CacheAdminService::class)
        ->constructor(
            get(CacheManager::class),
            get(ContentCacheService::class),
            dirname(__DIR__, 3) . '/storage/cache'
        ),

    CacheController::class => create(CacheController::class)
        ->constructor(
            get(CacheAdminService::class),
            get(SecurityLogger::class),
            get(JsonResponder::class)
        ),

    // Media module
    MediaRepositoryInterface::class => create(MediaRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(SettingsRepositoryInterface::class)
        ),

    StockImageCatalog::class => create(StockImageCatalog::class),

    StockImageImporter::class => create(StockImageImporter::class)
        ->constructor(
            get(MediaRepositoryInterface::class),
            get(SettingsRepositoryInterface::class),
            get(StockImageCatalog::class)
        ),

    NavigationRepositoryInterface::class => create(NavigationRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    NavigationController::class => create(NavigationController::class)
        ->constructor(
            get(NavigationRepositoryInterface::class),
            get(JsonResponder::class)
        ),

    CommentsRepositoryInterface::class => create(CommentsRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    CommentPolicyResolver::class => create(CommentPolicyResolver::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(ContentRepositoryInterface::class)
        ),
    CommentsController::class => create(CommentsController::class)
        ->constructor(
            get(CommentsRepositoryInterface::class),
            get(SettingsRepositoryInterface::class),
            get(CommentPolicyResolver::class),
            get(Validator::class),
            get(OtpWorkflowService::class),
            get(JsonResponder::class)
        ),

    MessageRepositoryInterface::class => create(MessageRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    ContactController::class => create(ContactController::class)
        ->constructor(
            get(MessageRepositoryInterface::class),
            get(Validator::class),
            get(JsonResponder::class)
        ),
    MessageController::class => create(MessageController::class)
        ->constructor(
            get(MessageRepositoryInterface::class),
            get(JsonResponder::class)
        ),

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
        ->constructor(
            get(GitHubService::class),
            get(JsonResponder::class)
        ),

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
        ->constructor(
            get(LockManagerInterface::class),
            get(JsonResponder::class)
        ),

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
            get(DevTokenRegistry::class),
            get(UserRepository::class)
        ),
    DeveloperLogger::class => create(DeveloperLogger::class),
    DeveloperModeMiddleware::class => create(DeveloperModeMiddleware::class)
        ->constructor(get(DeveloperModeGate::class)),

    EditorProfileService::class => create(EditorProfileService::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    EditorContentValidator::class => create(EditorContentValidator::class)
        ->constructor(get(EditorProfileService::class)),

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
            get(AuthenticationInterface::class),
            get(OtpWorkflowService::class),
            get(DynamicValidator::class),
            get(EditorContentValidator::class)
        ),
    AdvancedSearchService::class => create(AdvancedSearchService::class)
        ->constructor(
            get(ContentIndexService::class),
            get(\PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface::class)
        ),
    SearchController::class => create(SearchController::class)
        ->constructor(
            get(ContentIndexService::class),
            get(ContentRepositoryInterface::class),
            get(AdvancedSearchService::class),
            get(JsonResponder::class)
        ),
    MediaController::class => create(MediaController::class)
        ->constructor(
            get(MediaRepositoryInterface::class),
            get(FileReaderInterface::class),
            get(StockImageCatalog::class),
            get(StockImageImporter::class),
            get(JsonResponder::class)
        ),

    // Code editor / versioning / audit (auto-discovered admin routes)
    ConfigManager::class => create(ConfigManager::class),
    EventDispatcher::class => create(EventDispatcher::class),
    HookManager::class => create(HookManager::class),
    PluginPolicyScanner::class => create(PluginPolicyScanner::class)
        ->constructor(get(CodePolicyEngineInterface::class)),
    PluginRegistry::class => create(PluginRegistry::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/plugins.json'
        ),
    PluginImporter::class => create(PluginImporter::class)
        ->constructor(
            get(PluginRegistry::class),
            get(PluginPolicyScanner::class),
            dirname(__DIR__, 2) . '/Extensions',
            dirname(__DIR__, 2) . '/Routes/extensions',
            dirname(__DIR__, 4) . '/frontend/src/extensions',
            dirname(__DIR__, 4)
        ),
    PluginManagerInterface::class => create(PluginManager::class)
        ->constructor(
            get(PluginRegistry::class),
            get(PluginImporter::class),
            get(HookManager::class),
            dirname(__DIR__, 2) . '/Extensions',
            dirname(__DIR__, 2) . '/Routes/extensions',
            dirname(__DIR__, 4) . '/frontend/src/extensions'
        ),
    ExtensionsController::class => create(ExtensionsController::class)
        ->constructor(get(PluginManagerInterface::class), get(JsonResponder::class)),
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
            get(NotificationService::class),
            get(CacheManager::class)
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
            get(RealtimeTracker::class),
            get(JsonResponder::class)
        ),
    DashboardController::class => create(DashboardController::class)
        ->constructor(
            get(LockManagerInterface::class),
            get(ConflictLoggerInterface::class),
            get(HealthCheckManager::class),
            get(ReporterInterface::class),
            get(RealtimeTracker::class),
            get(ApplicationLogReader::class),
            get(AdminCountsService::class),
            get(JsonResponder::class)
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
        ->constructor(
            get(HealthCheckManager::class),
            get(JsonResponder::class)
        ),

    NotificationController::class => create(NotificationController::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(NotificationService::class),
            get(ReporterInterface::class),
            get(MonitoringReportScheduler::class),
            get(MonitoringScheduler::class),
            get(SchedulerStateStore::class),
            get(JsonResponder::class)
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
            get(DeveloperModeGate::class),
            get(JsonResponder::class)
        ),
    VersionController::class => create(VersionController::class)
        ->constructor(
            get(EnhancedVersionManager::class),
            get(ContentVersioningService::class),
            get(JsonResponder::class)
        ),
    AuditTrailController::class => create(AuditTrailController::class)
        ->constructor(
            get(AuditTrailService::class),
            get(JsonResponder::class)
        ),
    DeveloperController::class => create(DeveloperController::class)
        ->constructor(
            get(DeveloperModeGate::class),
            get(DeveloperMode::class),
            get(DeveloperLogger::class),
            get(TwoFactorInterface::class),
            get(UserRepository::class),
            get(AuthenticationInterface::class),
            get(JsonResponder::class)
        ),
    TrashService::class => create(TrashService::class)
        ->constructor(get(FileReaderInterface::class)),
    TrashController::class => create(TrashController::class)
        ->constructor(
            get(TrashService::class),
            get(ContentIndexService::class),
            get(ContentRepositoryInterface::class),
            get(JsonResponder::class)
        ),
    LoginAttemptTracker::class => create(LoginAttemptTracker::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(SettingsRepositoryInterface::class)
        ),
    FirewallScenarioRegistry::class => create(FirewallScenarioRegistry::class),
    FirewallScanner::class => create(FirewallScanner::class)
        ->constructor(get(FirewallScenarioRegistry::class)),
    FirewallBanStore::class => create(FirewallBanStore::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(SettingsRepositoryInterface::class)
        ),
    FirewallIncidentLogger::class => create(FirewallIncidentLogger::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(SettingsRepositoryInterface::class)
        ),
    FirewallService::class => create(FirewallService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(FirewallScanner::class),
            get(FirewallBanStore::class),
            get(FirewallIncidentLogger::class)
        ),
    FirewallController::class => create(FirewallController::class)
        ->constructor(
            get(FirewallService::class),
            get(JsonResponder::class)
        ),
    ApplicationLogReader::class => function (): ApplicationLogReader {
        $base = __DIR__ . '/../../../storage/logs';

        return new ApplicationLogReader([
            'app' => $base . '/app',
            'audit' => $base . '/audit',
            'event' => $base . '/event',
            'user' => $base . '/user',
        ]);
    },
    AccessLogService::class => create(AccessLogService::class)
        ->constructor(
            get(LogWriterInterface::class),
            get(SettingsRepositoryInterface::class)
        ),
    LogController::class => create(LogController::class)
        ->constructor(
            get(ApplicationLogReader::class),
            get(AccessLogService::class),
            get(JsonResponder::class)
        ),
    FeedGenerator::class => create(FeedGenerator::class)
        ->constructor(get(ContentIndexService::class), get(SettingsRepositoryInterface::class)),
    SitemapGenerator::class => create(SitemapGenerator::class)
        ->constructor(get(ContentIndexService::class), get(SettingsRepositoryInterface::class)),
    RobotsTxtGenerator::class => create(RobotsTxtGenerator::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    FeedController::class => create(FeedController::class)
        ->constructor(
            get(FeedGenerator::class),
            get(SitemapGenerator::class),
            get(RobotsTxtGenerator::class),
            get(ContentCacheService::class)
        ),
    SeoMetaBuilder::class => create(SeoMetaBuilder::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    PurgeContentCacheCommand::class => create(PurgeContentCacheCommand::class)
        ->constructor(
            get(ContentCacheService::class),
            get(ContentIndexService::class),
            get(ContentRepositoryInterface::class)
        ),
    ContentDiagnoseCommand::class => create(ContentDiagnoseCommand::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(ContentRepositoryInterface::class),
            get(ContentIndexService::class),
            get(ContentCacheService::class)
        ),
    SecurityAuditStore::class => create(SecurityAuditStore::class)
        ->constructor(get(FileReaderInterface::class)),
    AclRepository::class => create(AclRepository::class)
        ->constructor(get(FileReaderInterface::class)),
    PathAclService::class => create(PathAclService::class)
        ->constructor(get(AclRepository::class), get(AuthorizationInterface::class)),
    OAuthSsoService::class => create(OAuthSsoService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(UserRepository::class),
            get(SessionManager::class)
        ),
    SecurityAuditController::class => create(SecurityAuditController::class)
        ->constructor(get(SecurityAuditStore::class), get(JsonResponder::class)),
    AclController::class => create(AclController::class)
        ->constructor(get(AclRepository::class), get(SecurityLogger::class), get(JsonResponder::class)),
    SsoController::class => create(SsoController::class)
        ->constructor(
            get(OAuthSsoService::class),
            get(SettingsRepositoryInterface::class),
            get(SecurityLogger::class),
            get(JsonResponder::class)
        ),
    BlueprintRepository::class => create(BlueprintRepository::class)
        ->constructor(get(FileReaderInterface::class)),
    DynamicValidator::class => create(DynamicValidator::class)
        ->constructor(get(BlueprintRepository::class), get(Validator::class)),
    BlueprintController::class => create(BlueprintController::class)
        ->constructor(
            get(BlueprintRepository::class),
            get(DynamicValidator::class),
            get(JsonResponder::class)
        ),
    DemoMode::class => create(DemoMode::class),
    DemoLoginGuard::class => create(DemoLoginGuard::class)
        ->constructor(get(DemoMode::class)),
    DemoStorageService::class => create(DemoStorageService::class)
        ->constructor(get(DemoMode::class), get(FileReaderInterface::class)),
    DemoDataProviderInterface::class => create(DemoDataProvider::class)
        ->constructor(get(DemoMode::class)),
    DemoController::class => create(DemoController::class)
        ->constructor(get(DemoStorageService::class), get(JsonResponder::class)),
    DemoResetScheduler::class => create(DemoResetScheduler::class)
        ->constructor(get(DemoMode::class), get(DemoStorageService::class)),
    RunDemoResetCommand::class => create(RunDemoResetCommand::class)
        ->constructor(get(DemoResetScheduler::class)),
];
