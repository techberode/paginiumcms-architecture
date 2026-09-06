<?php

declare(strict_types=1);

use PaginiumCMS\Core\Admin\Services\AdminCountsService;
use PaginiumCMS\Core\Admin\Services\ContentStorageStatsService;
use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Blueprint\Services\BlueprintRepository;
use PaginiumCMS\Core\Blueprint\Services\DynamicValidator;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Middleware\AnalyticsMiddleware;
use PaginiumCMS\Core\Analytics\Services\AnalyticsManager;
use PaginiumCMS\Core\Analytics\Services\AnalyticsRetentionService;
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
use PaginiumCMS\Core\Cache\CacheDriverFactory;
use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Services\CacheCapabilityProbe;
use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\Cache\Commands\PurgeContentCacheCommand;
use PaginiumCMS\Core\Cache\Services\CacheAdminService;
use PaginiumCMS\Core\FlatFile\Commands\ContentDiagnoseCommand;
use PaginiumCMS\Core\FlatFile\Commands\ContentExportCommand;
use PaginiumCMS\Core\FlatFile\Commands\ContentImportCommand;
use PaginiumCMS\Core\FlatFile\Commands\ContentLocaleMigrateCommand;
use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\CodeEditor\Services\CodeEditorLogger;
use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;
use PaginiumCMS\Core\Layout\Services\ShortcodeDefinitionManager;
use PaginiumCMS\Core\Layout\Services\ShortcodeCatalogSeeder;
use PaginiumCMS\Core\Layout\Services\ShortcodeExpanderService;
use PaginiumCMS\Core\Layout\Services\ShortcodeRegistry;
use PaginiumCMS\Core\Snippets\Services\SnippetCatalogSeeder;
use PaginiumCMS\Core\Snippets\Services\SnippetInvalidationService;
use PaginiumCMS\Core\Snippets\Services\SnippetReferenceScanner;
use PaginiumCMS\Core\Snippets\Services\SnippetRegistry;
use PaginiumCMS\Core\Snippets\Services\SnippetRepository;
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
use PaginiumCMS\Core\I18n\Services\LocaleScaffoldService;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\I18n\Services\TranslationFileManager;
use PaginiumCMS\Core\I18n\Services\TranslationPolicyValidator;
use PaginiumCMS\Core\Git\Services\GitCapabilityProbe;
use PaginiumCMS\Core\Git\Services\GitPathValidator;
use PaginiumCMS\Core\Git\Services\GitPublishDispatcher;
use PaginiumCMS\Core\Git\Services\GitPublishService;
use PaginiumCMS\Core\Git\Services\GitPublishSettings;
use PaginiumCMS\Core\Git\Services\LocalGitProcess;
use PaginiumCMS\Core\Git\Services\LocalGitPublisher;
use PaginiumCMS\Core\Git\Services\PublishPlanner;
use PaginiumCMS\Core\Git\Services\PublishQueueStore;
use PaginiumCMS\Core\Hook\HookManager;
use PaginiumCMS\Core\Hook\Services\HookEmitter;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FrontMatterParserInterface;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownParserInterface;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\Content\BlogAuthorSettings;
use PaginiumCMS\Core\Content\Services\BlogSidebarService;
use PaginiumCMS\Core\Content\Services\CategoryCatalogSeeder;
use PaginiumCMS\Core\Content\Services\CategoryRepository;
use PaginiumCMS\Core\Content\LocalizedContentApplicator;
use PaginiumCMS\Core\Content\LocalizedContentMigrationService;
use PaginiumCMS\Core\Content\LocalizedContentNormalizer;
use PaginiumCMS\Core\Content\LocalizedContentValidator;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\Content\LocaleResolver;
use PaginiumCMS\Core\Conflict\Services\ConflictLogger;
use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Core\Drafts\Services\DraftManager;
use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Core\Editor\Services\EditorContentValidator;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Core\Editor\Services\TiptapHtmlRenderer;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\FlatFile\Services\ContentExportService;
use PaginiumCMS\Core\FlatFile\Services\ContentImportService;
use PaginiumCMS\Core\FlatFile\Services\ContentBulkTagService;
use PaginiumCMS\Core\FlatFile\Services\ContentDuplicationService;
use PaginiumCMS\Core\FlatFile\Services\ContentRepository;
use PaginiumCMS\Core\Import\WordPressWxrImporter;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Core\FlatFile\Services\JsonContentStorage;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentStorage;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Core\Feeds\Services\FeedGenerator;
use PaginiumCMS\Core\Feeds\Services\RobotsTxtGenerator;
use PaginiumCMS\Core\Feeds\Services\SitemapGenerator;
use PaginiumCMS\Core\Seo\Services\NotFoundHitStore;
use PaginiumCMS\Core\Seo\Services\RedirectStore;
use PaginiumCMS\Core\Webhooks\Services\OutboundWebhookDispatcher;
use PaginiumCMS\Core\Gdpr\Services\GdprExportService;
use PaginiumCMS\Core\Gdpr\Services\GdprAnonymizeService;
use PaginiumCMS\Core\Webhooks\Services\WebhookDeliveryService;
use PaginiumCMS\Core\Webhooks\Services\WebhookDeliveryStore;
use PaginiumCMS\Core\Webhooks\Services\WebhookHookRegistrar;
use PaginiumCMS\Core\Webhooks\Services\WebhookRegistryStore;
use PaginiumCMS\Core\Seo\Services\SeoMetaBuilder;
use PaginiumCMS\Core\Security\ClientIpResolver;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Core\Security\Firewall\FirewallBanStore;
use PaginiumCMS\Core\Security\Firewall\FirewallIncidentLogger;
use PaginiumCMS\Core\Security\Firewall\FirewallScenarioRegistry;
use PaginiumCMS\Core\Security\Firewall\FirewallScanner;
use PaginiumCMS\Core\Security\Firewall\FirewallService;
use PaginiumCMS\Core\Logging\LogStoragePaths;
use PaginiumCMS\Core\Logging\Services\ApplicationLogMessageFormatter;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Core\Logging\Services\AccessLogService;
use PaginiumCMS\Core\Logging\Services\LogRetentionService;
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentMetaGenerator;
use PaginiumCMS\Core\FlatFile\Services\ContentRevision;
use PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService;
use PaginiumCMS\Core\FlatFile\Services\FrontMatterParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownContentParser;
use PaginiumCMS\Core\FlatFile\Services\MarkdownParser;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Locking\Services\LockManager;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Core\Performance\Services\InstrumentedStorage;
use PaginiumCMS\Core\Storage\StorageFactory;
use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Core\Storage\Services\EngineCapabilityProbe;
use PaginiumCMS\Core\Validation\DocumentSchemaRegistry;
use PaginiumCMS\Core\Validation\DocumentValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Search\Services\AdvancedSearchService;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Versioning\Services\ContentVersioningService;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Http\Controllers\Admin\CacheController;
use PaginiumCMS\Http\Controllers\Admin\AnalyticsController;
use PaginiumCMS\Http\Controllers\Analytics\AnalyticsPageviewController;
use PaginiumCMS\Http\Controllers\Admin\AuditTrailController;
use PaginiumCMS\Http\Controllers\Admin\DashboardController;
use PaginiumCMS\Http\Controllers\Admin\HealthController;
use PaginiumCMS\Core\GitHub\Services\GitHubService;
use PaginiumCMS\Core\Scheduler\Handlers\GitPublishHandler;
use PaginiumCMS\Http\Controllers\Admin\GitHubController;
use PaginiumCMS\Http\Controllers\Admin\GitPublishController;
use PaginiumCMS\Http\Controllers\Admin\MessageController;
use PaginiumCMS\Http\Controllers\Admin\NotificationController;
use PaginiumCMS\Http\Controllers\Admin\CodeEditorController;
use PaginiumCMS\Http\Controllers\Admin\DemoController;
use PaginiumCMS\Http\Controllers\Admin\DeveloperController;
use PaginiumCMS\Http\Controllers\Admin\GatedCodeEditorController;
use PaginiumCMS\Http\Controllers\Admin\ExtensionsController;
use PaginiumCMS\Http\Controllers\Admin\ShortcodeController;
use PaginiumCMS\Http\Controllers\Admin\SnippetController;
use PaginiumCMS\Http\Controllers\Admin\ThemesController;
use PaginiumCMS\Http\Controllers\Admin\BlueprintController;
use PaginiumCMS\Core\Setup\Services\FirstAdminBootstrapService;
use PaginiumCMS\Core\Setup\Services\SetupPreflightService;
use PaginiumCMS\Core\Setup\Services\SetupStatusService;
use PaginiumCMS\Http\Controllers\Setup\SetupController;
use PaginiumCMS\Http\Controllers\Origin\OriginController;
use PaginiumCMS\Http\Controllers\Admin\AclController;
use PaginiumCMS\Http\Controllers\Admin\SecurityAuditController;
use PaginiumCMS\Http\Controllers\Admin\ApiKeyController;
use PaginiumCMS\Http\Controllers\Admin\NotFoundReportController;
use PaginiumCMS\Http\Controllers\Admin\WebhookController;
use PaginiumCMS\Http\Controllers\Admin\GdprController;
use PaginiumCMS\Http\Controllers\Admin\RedirectController;
use PaginiumCMS\Http\Controllers\Headless\HeadlessTokenController;
use PaginiumCMS\Http\Controllers\Admin\SettingsController;
use PaginiumCMS\Http\Controllers\Admin\TranslationController;
use PaginiumCMS\Http\Controllers\Auth\SsoController;
use PaginiumCMS\Http\Controllers\Admin\TrashController;
use PaginiumCMS\Http\Controllers\Admin\FirewallController;
use PaginiumCMS\Http\Controllers\Admin\LogController;
use PaginiumCMS\Http\Controllers\Gallery\GalleryAdminController;
use PaginiumCMS\Http\Controllers\Gallery\GalleryPublicController;
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
use PaginiumCMS\Http\Controllers\Content\BlogSidebarController;
use PaginiumCMS\Http\Controllers\Content\CategoriesController;
use PaginiumCMS\Http\Controllers\Content\ContentController;
use PaginiumCMS\Http\Controllers\Content\ContentMetaController;
use PaginiumCMS\Http\Controllers\Content\EditorialCalendarController;
use PaginiumCMS\Http\Controllers\Content\DraftController;
use PaginiumCMS\Http\Controllers\Content\SearchController;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PaginiumCMS\Http\Extensions\Services\ExtensionManifestValidator;
use PaginiumCMS\Http\Extensions\Services\PluginImporter;
use PaginiumCMS\Http\Extensions\Services\PluginManager;
use PaginiumCMS\Http\Extensions\Services\PluginPolicyScanner;
use PaginiumCMS\Http\Extensions\Services\PluginRegistry;
use PaginiumCMS\Http\Themes\Services\ThemeCatalogSeeder;
use PaginiumCMS\Http\Themes\Services\ThemeImporter;
use PaginiumCMS\Http\Themes\Services\ThemeManager;
use PaginiumCMS\Http\Themes\Services\ThemeManifestValidator;
use PaginiumCMS\Http\Themes\Services\ThemeStarterPackageService;
use PaginiumCMS\Http\Themes\Services\ThemeRegistry;
use PaginiumCMS\Http\Themes\Services\ThemeRuntimeService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Controllers\Locking\LockController;
use PaginiumCMS\Http\Controllers\Media\MediaController;
use PaginiumCMS\Http\Middleware\AnalyticsPageviewRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\DeveloperModeMiddleware;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Services\CommentPolicyResolver;
use PaginiumCMS\Modules\Comments\Services\CommentSpamHeuristicService;
use PaginiumCMS\Modules\Comments\Services\CommentSubmissionVelocityStore;
use PaginiumCMS\Modules\Comments\Services\DisposableEmailDomainList;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;
use PaginiumCMS\Modules\Navigation\Services\NavigationRepository;
use PaginiumCMS\Modules\Navigation\Services\NavigationRichFieldValidator;
use PaginiumCMS\Modules\Gallery\Contracts\GalleryRepositoryInterface;
use PaginiumCMS\Modules\Gallery\Services\GalleryRepository;
use PaginiumCMS\Modules\Gallery\Services\GalleryItemValidator;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaImageOptimizer;
use PaginiumCMS\Modules\Media\Services\MediaOptimizePreviewStore;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PaginiumCMS\Modules\Media\Services\MediaStorageCapabilityProbe;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
use PaginiumCMS\Modules\Media\Services\StockImageCatalog;
use PaginiumCMS\Modules\Media\Services\StockImageImporter;
use PaginiumCMS\Modules\Demo\Contracts\DemoDataProviderInterface;
use PaginiumCMS\Modules\Demo\Commands\RunDemoResetCommand;
use PaginiumCMS\Modules\Demo\Services\DemoDataProvider;
use PaginiumCMS\Modules\Demo\Services\DemoLoginGuard;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Origin\Services\FeatureProbeRegistry;
use PaginiumCMS\Modules\Origin\Services\CatalogDeployStatusResolver;
use PaginiumCMS\Modules\Origin\Services\ImplementationChecklistReader;
use PaginiumCMS\Modules\Origin\Services\OriginCatalogLabelResolver;
use PaginiumCMS\Modules\Origin\Services\ProjectCatalogMergeService;
use PaginiumCMS\Modules\Origin\Services\ProjectCatalogReader;
use PaginiumCMS\Modules\Origin\Services\OriginPanelMode;
use PaginiumCMS\Modules\Origin\Services\ProbeSupport;
use PaginiumCMS\Modules\Demo\Services\DemoStorageQuotaService;
use PaginiumCMS\Modules\Demo\Services\DemoResetScheduler;
use PaginiumCMS\Modules\Demo\Services\DemoStorageService;
use PaginiumCMS\Modules\Security\Services\AclRepository;
use PaginiumCMS\Modules\Security\Services\AccessControlSyncService;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\OAuthSsoService;
use PaginiumCMS\Modules\Security\Services\ContentPathAclGuard;
use PaginiumCMS\Modules\Security\Services\PathAclService;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Core\Content\AvatarImageProcessor;
use PaginiumCMS\Modules\Security\Services\UserAvatarService;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Services\RoleCatalogSeeder;
use PaginiumCMS\Modules\Security\Services\RoleRepository;
use PaginiumCMS\Http\Controllers\Admin\RolesController;

use function DI\create;
use function DI\get;

return [
    // FlatFile content stack
    FrontMatterParserInterface::class => create(FrontMatterParser::class),
    MarkdownContentParserInterface::class => create(MarkdownContentParser::class),
    ContentMetaGenerator::class => create(ContentMetaGenerator::class)
        ->constructor(get(MarkdownContentParserInterface::class)),
    TiptapHtmlRenderer::class => create(TiptapHtmlRenderer::class),
    ContentSecuritySanitizer::class => create(ContentSecuritySanitizer::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    UploadSecurityValidator::class => create(UploadSecurityValidator::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    ContentBodyRenderer::class => create(ContentBodyRenderer::class)
        ->constructor(
            get(MarkdownContentParserInterface::class),
            get(TiptapHtmlRenderer::class),
            get(ContentSecuritySanitizer::class),
            get(ShortcodeExpanderService::class)
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
            get(LocalizedContentNormalizer::class),
            get(ContentStalenessService::class),
            'data/index/content.json'
        ),
    ContentStalenessService::class => create(ContentStalenessService::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    JsonResponder::class => create(JsonResponder::class),
    ContentRepositoryInterface::class => create(ContentRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(ContentIndexService::class),
            get(MarkdownContentStorage::class),
            get(JsonContentStorage::class),
            get(SettingsRepositoryInterface::class),
            get(StorageInterface::class),
            get(GitPublishDispatcher::class),
            get(LocalizedContentWriter::class)
        ),
    ContentDuplicationService::class => create(ContentDuplicationService::class)
        ->constructor(
            get(ContentRepositoryInterface::class),
            get(DynamicValidator::class)
        ),
    ContentBulkTagService::class => create(ContentBulkTagService::class),

    // === Blok: Hybrid Engine storage (Iteration 68) ===
    DocumentSchemaRegistry::class => function () {
        return DocumentSchemaRegistry::createWithDefaults();
    },
    DocumentValidator::class => create(DocumentValidator::class)
        ->constructor(get(DocumentSchemaRegistry::class)),
    StorageFactory::class => create(StorageFactory::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(\PaginiumCMS\Core\FlatFile\Services\FileValidator::class)
        ),
    StorageInterface::class => function ($container) {
        /** @var StorageFactory $factory */
        $factory = $container->get(StorageFactory::class);
        $inner = $factory->create(null, true);

        return new InstrumentedStorage(
            $inner,
            $container->get(PerformanceContext::class)
        );
    },
    EngineCapabilityProbe::class => create(EngineCapabilityProbe::class),
    CacheDriverFactory::class => function () {
        return new CacheDriverFactory(dirname(__DIR__, 3) . '/storage/cache');
    },
    CacheManager::class => function ($container) {
        /** @var CacheDriverFactory $factory */
        $factory = $container->get(CacheDriverFactory::class);
        $settings = $container->get(SettingsRepositoryInterface::class);
        $engine = $settings->group('engine');
        $driver = $factory->create(CacheDriverFactory::driverFromEngineSettings($engine));

        return new CacheManager(
            $driver,
            'paginium_',
            dirname(__DIR__, 3) . '/storage/cache/locks'
        );
    },
    CacheCapabilityProbe::class => create(CacheCapabilityProbe::class),

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
            $container->get(FileWriterInterface::class),
            $container->get(StorageInterface::class),
            $container->get(Validator::class),
            $settingsFile,
            $container->get(\PaginiumCMS\Core\Security\Services\EncryptionService::class),
            $container->get(DocumentValidator::class)
        );
    },
    AccessControlSyncService::class => create(AccessControlSyncService::class)
        ->constructor(get(AclRepository::class)),

    RoleRepository::class => create(RoleRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
        ),

    RoleCatalogSeeder::class => create(RoleCatalogSeeder::class)
        ->constructor(get(RoleRepository::class)),

    RolesController::class => create(RolesController::class)
        ->constructor(
            get(RoleRepository::class),
            get(RoleCatalogSeeder::class),
            get(UserRepository::class),
            get(AuthorizationManager::class),
            get(JsonResponder::class),
        ),

    SettingsController::class => create(SettingsController::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(JsonResponder::class),
            get(SecurityLogger::class),
            get(DemoMode::class),
            get(EditorProfileService::class),
            get(EditorComponentRegistry::class),
            get(AccessControlSyncService::class),
            get(RoleCatalogSeeder::class),
            get(AuthorizationInterface::class),
            get(SupportedLocalesRegistry::class),
            get(EngineCapabilityProbe::class),
            get(CacheCapabilityProbe::class),
            get(CacheDriverFactory::class),
            get(StorageInterface::class),
            get(GitCapabilityProbe::class),
            get(MediaStorageFactory::class),
            get(MediaStorageCapabilityProbe::class),
            get(ThemeRuntimeService::class),
        ),

    GitPathValidator::class => create(GitPathValidator::class),
    LocalGitProcess::class => create(LocalGitProcess::class),
    GitPublishSettings::class => create(GitPublishSettings::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    PublishQueueStore::class => create(PublishQueueStore::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    PublishPlanner::class => create(PublishPlanner::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    LocalGitPublisher::class => create(LocalGitPublisher::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(LocalGitProcess::class),
            get(GitPathValidator::class)
        ),
    GitPublishService::class => create(GitPublishService::class)
        ->constructor(
            get(GitPublishSettings::class),
            get(PublishQueueStore::class),
            get(PublishPlanner::class),
            get(LocalGitPublisher::class),
            get(GitPathValidator::class),
            get(LoggerInterface::class)
        ),
    GitPublishDispatcher::class => create(GitPublishDispatcher::class)
        ->constructor(get(GitPublishService::class)),
    GitCapabilityProbe::class => create(GitCapabilityProbe::class)
        ->constructor(
            get(GitPublishSettings::class),
            get(LocalGitProcess::class),
            get(GitPathValidator::class),
            get(SettingsRepositoryInterface::class)
        ),
    GitPublishController::class => create(GitPublishController::class)
        ->constructor(get(GitPublishService::class), get(JsonResponder::class)),
    GitPublishHandler::class => create(GitPublishHandler::class)
        ->constructor(get(GitPublishService::class)),

    TranslationFileManagerInterface::class => create(TranslationFileManager::class)
        ->constructor(
            get(TranslationPolicyValidator::class),
            get(SupportedLocalesRegistry::class),
            get(LocaleScaffoldService::class),
            get(FileBackup::class)
        ),
    SupportedLocalesRegistry::class => create(SupportedLocalesRegistry::class),
    LocaleScaffoldService::class => create(LocaleScaffoldService::class)
        ->constructor(get(SupportedLocalesRegistry::class)),
    UserAvatarService::class => create(UserAvatarService::class)
        ->constructor(
            get(MediaRepositoryInterface::class),
            get(AvatarImageProcessor::class),
        ),
    AvatarImageProcessor::class => create(AvatarImageProcessor::class),
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
            get(NewsletterRepositoryInterface::class),
            get(BackupInterface::class),
            get(TrashService::class),
            get(UserRepository::class),
            get(FirewallService::class)
        ),
    ContentStorageStatsService::class => create(ContentStorageStatsService::class)
        ->constructor(get(FileReaderInterface::class)),

    CountsController::class => create(CountsController::class)
        ->constructor(
            get(AdminCountsService::class),
            get(JsonResponder::class)
        ),

    ValidationController::class => function ($container) {
        return new ValidationController(
            $container->get(JsonResponder::class),
            $container->get(PasswordPolicyInterface::class)
        );
    },

    UserController::class => create(UserController::class)
        ->constructor(
            get(UserRepository::class),
            get(UserAvatarService::class),
            get(SettingsRepositoryInterface::class),
            get(RoleRepository::class),
            get(RoleCatalogSeeder::class),
            get(Validator::class),
            get(PasswordPolicyInterface::class),
            get(JsonResponder::class)
        ),

    // Revízny odtlačok obsahu (optimistické zamykanie / detekcia konfliktov – Iterácia 2)
    ContentRevision::class => create(ContentRevision::class),

    LocaleResolver::class => create(LocaleResolver::class)
        ->constructor(
            get(SupportedLocalesRegistry::class),
            get(SettingsRepositoryInterface::class)
        ),
    LocalizedContentNormalizer::class => create(LocalizedContentNormalizer::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    LocalizedContentApplicator::class => create(LocalizedContentApplicator::class),
    LocalizedContentValidator::class => create(LocalizedContentValidator::class)
        ->constructor(get(SupportedLocalesRegistry::class)),
    LocalizedContentWriter::class => create(LocalizedContentWriter::class)
        ->constructor(get(LocalizedContentNormalizer::class)),
    LocalizedContentMigrationService::class => create(LocalizedContentMigrationService::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(ContentRepositoryInterface::class),
            get(ContentIndexService::class),
            get(ContentCacheService::class),
            get(LocalizedContentNormalizer::class),
            get(LocalizedContentWriter::class),
            get(SettingsRepositoryInterface::class)
        ),

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
            get(JsonResponder::class),
            get(ContentPathAclGuard::class)
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
    MediaStorageFactory::class => create(MediaStorageFactory::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    MediaStorageCapabilityProbe::class => create(MediaStorageCapabilityProbe::class),

    MediaImageOptimizer::class => create(MediaImageOptimizer::class),

    MediaOptimizePreviewStore::class => create(MediaOptimizePreviewStore::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),

    MediaRepositoryInterface::class => create(MediaRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(SettingsRepositoryInterface::class),
            get(UploadSecurityValidator::class),
            get(MediaStorageFactory::class),
            get(MediaImageOptimizer::class),
            get(MediaOptimizePreviewStore::class)
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
    NavigationRichFieldValidator::class => create(NavigationRichFieldValidator::class),
    NavigationController::class => create(NavigationController::class)
        ->constructor(
            get(NavigationRepositoryInterface::class),
            get(NavigationRichFieldValidator::class),
            get(SettingsRepositoryInterface::class),
            get(JsonResponder::class)
        ),

    GalleryRepositoryInterface::class => create(GalleryRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    GalleryItemValidator::class => create(GalleryItemValidator::class),
    GalleryAdminController::class => create(GalleryAdminController::class)
        ->constructor(
            get(GalleryRepositoryInterface::class),
            get(GalleryItemValidator::class),
            get(JsonResponder::class)
        ),
    GalleryPublicController::class => create(GalleryPublicController::class)
        ->constructor(
            get(GalleryRepositoryInterface::class),
            get(SettingsRepositoryInterface::class),
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
            get(ContentRepositoryInterface::class),
            get(CommentSpamHeuristicService::class)
        ),
    DisposableEmailDomainList::class => create(DisposableEmailDomainList::class),
    CommentSubmissionVelocityStore::class => create(CommentSubmissionVelocityStore::class),
    CommentSpamHeuristicService::class => create(CommentSpamHeuristicService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(DisposableEmailDomainList::class),
            get(CommentSubmissionVelocityStore::class)
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
    PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken::class => function () {
        $appKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? null);

        return new PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken(
            is_string($appKey) ? $appKey : null
        );
    },
    PaginiumCMS\Modules\Newsletter\Services\NewsletterLinkBuilder::class => create(PaginiumCMS\Modules\Newsletter\Services\NewsletterLinkBuilder::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken::class)
        ),
    PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class => create(PaginiumCMS\Modules\Newsletter\Services\NewsletterRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken::class)
        ),
    PaginiumCMS\Modules\Newsletter\Services\NewsletterSubscribeService::class => create(PaginiumCMS\Modules\Newsletter\Services\NewsletterSubscribeService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService::class),
            get(Validator::class)
        ),
    PaginiumCMS\Modules\Newsletter\Services\NewsletterSendStateStore::class => create(PaginiumCMS\Modules\Newsletter\Services\NewsletterSendStateStore::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService::class => create(PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService::class)
        ->constructor(
            get(\PaginiumCMS\Core\Notification\NotificationService::class),
            get(SettingsRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class),
            get(\PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Services\NewsletterSendStateStore::class),
            get(PaginiumCMS\Modules\Newsletter\Services\NewsletterLinkBuilder::class)
        ),
    PaginiumCMS\Modules\Newsletter\Services\NewsletterHookRegistrar::class => create(PaginiumCMS\Modules\Newsletter\Services\NewsletterHookRegistrar::class)
        ->constructor(
            get(HookManager::class),
            get(PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService::class)
        ),
    PaginiumCMS\Http\Controllers\Admin\NewsletterAdminController::class => create(PaginiumCMS\Http\Controllers\Admin\NewsletterAdminController::class)
        ->constructor(
            get(PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService::class),
            get(Validator::class),
            get(JsonResponder::class)
        ),
    PaginiumCMS\Http\Controllers\Newsletter\NewsletterController::class => create(PaginiumCMS\Http\Controllers\Newsletter\NewsletterController::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Services\NewsletterSubscribeService::class),
            get(PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class),
            get(JsonResponder::class)
        ),
    PaginiumCMS\Http\Controllers\Maintenance\MaintenanceController::class => create(PaginiumCMS\Http\Controllers\Maintenance\MaintenanceController::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(PaginiumCMS\Modules\Newsletter\Services\NewsletterSubscribeService::class),
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
            $localEnvs = ['testing', 'test', 'development', 'local'];

            // Fail-closed (audit 2026-07-22): predvídateľný fallback secret sa
            // použije IBA v explicitných lokálnych/testovacích prostrediach.
            // Predtým stačil APP_DEBUG=true (default!) → riziko v produkcii.
            //
            // Mimo lokál/test necháme secret PRÁZDNY. DevTokenGenerator je
            // fail-closed pri použití (isConfigured()===false → generate()
            // vyhodí, validate()/verifyStructure() vrátia „nie je nastavený"),
            // takže dev-unlock je bezpečne vypnutý a NIE je predvídateľný.
            // Nevyhadzujeme výnimku vo factory – rozbila by boot kontajnera
            // (developer routy resolvujú generator už pri registrácii).
            if (in_array($appEnv, $localEnvs, true)) {
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

    EditorComponentRegistry::class => create(EditorComponentRegistry::class)
        ->constructor(get(PluginManagerInterface::class)),
    EditorProfileService::class => create(EditorProfileService::class)
        ->constructor(get(SettingsRepositoryInterface::class), get(EditorComponentRegistry::class)),
    EditorContentValidator::class => create(EditorContentValidator::class)
        ->constructor(get(EditorProfileService::class), get(EditorComponentRegistry::class)),

    BlogAuthorSettings::class => create(BlogAuthorSettings::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(\PaginiumCMS\Modules\Security\Services\UserRepository::class),
        ),

    BlogSidebarService::class => create(BlogSidebarService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(ContentRepositoryInterface::class),
            get(ReporterInterface::class),
            get(CategoryRepository::class),
            get(CategoryCatalogSeeder::class),
        ),

    CategoryRepository::class => create(CategoryRepository::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
        ),

    CategoryCatalogSeeder::class => create(CategoryCatalogSeeder::class)
        ->constructor(get(CategoryRepository::class)),

    CategoriesController::class => create(CategoriesController::class)
        ->constructor(
            get(CategoryRepository::class),
            get(CategoryCatalogSeeder::class),
            get(JsonResponder::class),
        ),

    BlogSidebarController::class => create(BlogSidebarController::class)
        ->constructor(
            get(BlogSidebarService::class),
            get(ContentCacheService::class),
            get(JsonResponder::class),
            get(SettingsRepositoryInterface::class),
        ),

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
            get(EditorContentValidator::class),
            get(ContentPathAclGuard::class),
            get(HookEmitter::class),
            get(LocaleResolver::class),
            get(LocalizedContentNormalizer::class),
            get(LocalizedContentApplicator::class),
            get(LocalizedContentValidator::class),
            get(LocalizedContentWriter::class),
            get(BlogAuthorSettings::class),
            get(ContentDuplicationService::class),
            get(ContentBulkTagService::class),
            get(ContentStalenessService::class),
            get(BlogSidebarService::class),
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
            get(AuthenticationInterface::class),
            get(JsonResponder::class)
        ),
    EditorialCalendarController::class => create(EditorialCalendarController::class)
        ->constructor(
            get(ContentIndexService::class),
            get(ContentRepositoryInterface::class),
            get(ContentPathAclGuard::class),
            get(JsonResponder::class)
        ),
    ContentMetaController::class => create(ContentMetaController::class)
        ->constructor(
            get(ContentMetaGenerator::class),
            get(ContentBodyRenderer::class),
            get(SettingsRepositoryInterface::class),
            get(JsonResponder::class)
        ),
    MediaController::class => create(MediaController::class)
        ->constructor(
            get(MediaRepositoryInterface::class),
            get(FileReaderInterface::class),
            get(StockImageCatalog::class),
            get(StockImageImporter::class),
            get(JsonResponder::class),
            get(ContentPathAclGuard::class)
        ),

    // Code editor / versioning / audit (auto-discovered admin routes)
    ConfigManager::class => create(ConfigManager::class),
    EventDispatcher::class => create(EventDispatcher::class),
    HookManager::class => create(HookManager::class),
    HookEmitter::class => create(HookEmitter::class)
        ->constructor(get(HookManager::class)),
    ExtensionManifestValidator::class => create(ExtensionManifestValidator::class),
    UntrustedPolicyScanner::class => create(UntrustedPolicyScanner::class)
        ->constructor(get(CodePolicyEngineInterface::class)),
    PluginPolicyScanner::class => create(PluginPolicyScanner::class)
        ->constructor(get(UntrustedPolicyScanner::class)),
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
            get(ExtensionManifestValidator::class),
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
            get(HookEmitter::class),
            get(ExtensionManifestValidator::class),
            dirname(__DIR__, 2) . '/Extensions',
            dirname(__DIR__, 2) . '/Routes/extensions',
            dirname(__DIR__, 4) . '/frontend/src/extensions'
        ),
    ExtensionsController::class => create(ExtensionsController::class)
        ->constructor(get(PluginManagerInterface::class), get(JsonResponder::class)),
    ShortcodeRegistry::class => create(ShortcodeRegistry::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/shortcodes/registry.json'
        ),
    ShortcodeDefinitionPolicy::class => create(ShortcodeDefinitionPolicy::class),
    ShortcodeDefinitionManager::class => create(ShortcodeDefinitionManager::class)
        ->constructor(
            get(ShortcodeDefinitionPolicy::class),
            get(CodePolicyEngineInterface::class),
            get(ShortcodeRegistry::class),
            get(FileReaderInterface::class),
            get(FileWriterInterface::class)
        ),
    ShortcodeExpanderService::class => create(ShortcodeExpanderService::class)
        ->constructor(
            get(ShortcodeRegistry::class),
            get(FileReaderInterface::class),
            get(ContentSecuritySanitizer::class),
            get(SnippetRepository::class)
        ),
    SnippetRegistry::class => create(SnippetRegistry::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/snippets/registry.json'
        ),
    SnippetRepository::class => create(SnippetRepository::class)
        ->constructor(
            get(SnippetRegistry::class),
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            get(ContentSecuritySanitizer::class)
        ),
    SnippetReferenceScanner::class => create(SnippetReferenceScanner::class)
        ->constructor(get(FileReaderInterface::class)),
    SnippetInvalidationService::class => create(SnippetInvalidationService::class)
        ->constructor(
            get(SnippetReferenceScanner::class),
            get(ContentCacheService::class)
        ),
    SnippetCatalogSeeder::class => create(SnippetCatalogSeeder::class)
        ->constructor(
            get(SnippetRepository::class),
            get(SnippetRegistry::class)
        ),
    SnippetController::class => create(SnippetController::class)
        ->constructor(
            get(SnippetRepository::class),
            get(SnippetCatalogSeeder::class),
            get(SnippetInvalidationService::class),
            get(ShortcodeExpanderService::class),
            get(JsonResponder::class)
        ),
    ShortcodeCatalogSeeder::class => create(ShortcodeCatalogSeeder::class)
        ->constructor(
            get(ShortcodeDefinitionManager::class),
            get(ShortcodeRegistry::class),
            get(ContentCacheService::class)
        ),
    ShortcodeController::class => create(ShortcodeController::class)
        ->constructor(
            get(ShortcodeDefinitionManager::class),
            get(ShortcodeCatalogSeeder::class),
            get(ContentCacheService::class),
            get(JsonResponder::class)
        ),
    ThemeManifestValidator::class => create(ThemeManifestValidator::class),
    ThemeRegistry::class => create(ThemeRegistry::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(FileWriterInterface::class),
            'data/themes.json'
        ),
    ThemeImporter::class => create(ThemeImporter::class)
        ->constructor(
            get(ThemeRegistry::class),
            get(UntrustedPolicyScanner::class),
            get(ThemeManifestValidator::class),
            dirname(__DIR__, 3) . '/resources/views/themes',
            dirname(__DIR__, 4) . '/frontend/src/themes',
            dirname(__DIR__, 4)
        ),
    ThemeStarterPackageService::class => create(ThemeStarterPackageService::class)
        ->constructor(dirname(__DIR__, 3) . '/resources/theme-packages'),
    ThemeCatalogSeeder::class => create(ThemeCatalogSeeder::class)
        ->constructor(
            get(ThemeRegistry::class),
            dirname(__DIR__, 3) . '/resources/theme-packages',
            dirname(__DIR__, 3) . '/resources/views/themes'
        ),
    ThemeRuntimeService::class => create(ThemeRuntimeService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(ThemeRegistry::class),
            get(ContentCacheService::class),
            dirname(__DIR__, 3) . '/resources/views/themes'
        ),
    ThemeManager::class => create(ThemeManager::class)
        ->constructor(
            get(ThemeRegistry::class),
            get(ThemeImporter::class),
            get(ThemeRuntimeService::class),
            get(ThemeCatalogSeeder::class),
            dirname(__DIR__, 3) . '/resources/views/themes',
            dirname(__DIR__, 4) . '/frontend/src/themes'
        ),
    ThemesController::class => create(ThemesController::class)
        ->constructor(
            get(ThemeManager::class),
            get(ThemeStarterPackageService::class),
            get(JsonResponder::class)
        ),
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
            get(CodePolicyEngineInterface::class),
            get(ShortcodeDefinitionPolicy::class)
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
    AnalyticsRetentionService::class => create(AnalyticsRetentionService::class)
        ->constructor(
            get(FileReaderInterface::class),
            get(SettingsRepositoryInterface::class),
            'data/analytics'
        ),
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
            get(CacheManager::class),
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
            get(\PaginiumCMS\Core\Security\Firewall\FirewallService::class),
            get(JsonResponder::class)
        ),
    AnalyticsPageviewController::class => create(AnalyticsPageviewController::class)
        ->constructor(get(AnalyticsManager::class), get(JsonResponder::class)),
    AnalyticsPageviewRateLimitMiddleware::class => create(AnalyticsPageviewRateLimitMiddleware::class)
        ->constructor(get(CacheManager::class), ClientIpResolver::trustedProxiesFromEnv()),
    DashboardController::class => create(DashboardController::class)
        ->constructor(
            get(LockManagerInterface::class),
            get(ConflictLoggerInterface::class),
            get(HealthCheckManager::class),
            get(ReporterInterface::class),
            get(RealtimeTracker::class),
            get(ApplicationLogReader::class),
            get(AdminCountsService::class),
            get(ContentStorageStatsService::class),
            get(JsonResponder::class)
        ),

    // === Blok: Health checks (Iteration 7) ===
    SystemChecker::class => create(SystemChecker::class),
    DemoStorageQuotaService::class => create(DemoStorageQuotaService::class)
        ->constructor(get(DemoMode::class)),
    StorageChecker::class => create(StorageChecker::class)
        ->constructor(dirname(__DIR__, 3) . '/storage', get(DemoStorageQuotaService::class)),
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
    ContentScheduledPublishService::class => create(ContentScheduledPublishService::class)
        ->constructor(
            get(ContentRepositoryInterface::class),
            get(ContentVersioningService::class),
            get(ContentCacheService::class),
            get(\PaginiumCMS\Core\Workflow\Services\OtpWorkflowService::class),
            get(HookEmitter::class)
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
        return new ApplicationLogReader(LogStoragePaths::readerSources());
    },
    ApplicationLogMessageFormatter::class => create(ApplicationLogMessageFormatter::class),
    AccessLogService::class => create(AccessLogService::class)
        ->constructor(
            get(LogWriterInterface::class),
            get(SettingsRepositoryInterface::class)
        ),
    LogController::class => create(LogController::class)
        ->constructor(
            get(ApplicationLogReader::class),
            get(ApplicationLogMessageFormatter::class),
            get(LogRetentionService::class),
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
    RedirectStore::class => create(RedirectStore::class)
        ->constructor(get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class)),
    NotFoundHitStore::class => create(NotFoundHitStore::class)
        ->constructor(get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class)),
    WebhookRegistryStore::class => create(WebhookRegistryStore::class)
        ->constructor(
            get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class),
            get(\PaginiumCMS\Core\Security\Services\EncryptionService::class)
        ),
    WebhookDeliveryStore::class => create(WebhookDeliveryStore::class)
        ->constructor(get(\PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface::class)),
    WebhookDeliveryService::class => create(WebhookDeliveryService::class)
        ->constructor(
            get(WebhookRegistryStore::class),
            get(WebhookDeliveryStore::class)
        ),
    OutboundWebhookDispatcher::class => create(OutboundWebhookDispatcher::class)
        ->constructor(
            get(WebhookRegistryStore::class),
            get(WebhookDeliveryStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobRegistryStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobQueueStore::class),
            get(\PaginiumCMS\Core\Scheduler\Services\JobWorker::class)
        ),
    WebhookHookRegistrar::class => create(WebhookHookRegistrar::class)
        ->constructor(
            get(HookManager::class),
            get(OutboundWebhookDispatcher::class)
        ),
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
    ContentExportService::class => create(ContentExportService::class)
        ->constructor(get(ContentRepositoryInterface::class)),
    WordPressWxrImporter::class => create(WordPressWxrImporter::class),
    ContentImportService::class => create(ContentImportService::class)
        ->constructor(
            get(ContentRepositoryInterface::class),
            get(WordPressWxrImporter::class)
        ),
    ContentExportCommand::class => create(ContentExportCommand::class)
        ->constructor(get(ContentExportService::class)),
    ContentImportCommand::class => create(ContentImportCommand::class)
        ->constructor(get(ContentImportService::class)),
    ContentLocaleMigrateCommand::class => create(ContentLocaleMigrateCommand::class)
        ->constructor(get(LocalizedContentMigrationService::class)),
    SecurityAuditStore::class => create(SecurityAuditStore::class)
        ->constructor(get(FileReaderInterface::class)),
    AclRepository::class => create(AclRepository::class)
        ->constructor(get(FileReaderInterface::class)),
    PathAclService::class => create(PathAclService::class)
        ->constructor(get(AclRepository::class), get(AuthorizationInterface::class)),
    ContentPathAclGuard::class => create(ContentPathAclGuard::class)
        ->constructor(get(PathAclService::class)),
    OAuthSsoService::class => create(OAuthSsoService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(UserRepository::class),
            get(SessionManager::class)
        ),
    SecurityAuditController::class => create(SecurityAuditController::class)
        ->constructor(get(SecurityAuditStore::class), get(JsonResponder::class)),
    ApiKeyController::class => create(ApiKeyController::class)
        ->constructor(
            get(\PaginiumCMS\Modules\Security\Services\ApiKeyStore::class),
            get(\PaginiumCMS\Modules\Security\Services\ApiKeyVerifier::class),
            get(\PaginiumCMS\Modules\Security\Services\ApiJwtService::class),
            get(SecurityAuditStore::class),
            get(JsonResponder::class)
        ),
    RedirectController::class => create(RedirectController::class)
        ->constructor(
            get(RedirectStore::class),
            get(JsonResponder::class)
        ),
    NotFoundReportController::class => create(NotFoundReportController::class)
        ->constructor(
            get(NotFoundHitStore::class),
            get(JsonResponder::class)
        ),
    WebhookController::class => create(WebhookController::class)
        ->constructor(
            get(WebhookRegistryStore::class),
            get(WebhookDeliveryStore::class),
            get(OutboundWebhookDispatcher::class),
            get(JsonResponder::class)
        ),
    GdprExportService::class => create(GdprExportService::class)
        ->constructor(
            get(\PaginiumCMS\Modules\Comments\Services\CommentsRepository::class),
            get(\PaginiumCMS\Modules\Messages\Services\MessageRepository::class),
            get(\PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class)
        ),
    GdprAnonymizeService::class => create(GdprAnonymizeService::class)
        ->constructor(
            get(UserRepository::class),
            get(UserAvatarService::class),
            get(\PaginiumCMS\Modules\Comments\Services\CommentsRepository::class),
            get(\PaginiumCMS\Modules\Messages\Services\MessageRepository::class),
            get(\PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class)
        ),
    GdprController::class => create(GdprController::class)
        ->constructor(
            get(UserRepository::class),
            get(GdprExportService::class),
            get(GdprAnonymizeService::class),
            get(SecurityAuditStore::class),
            get(JsonResponder::class)
        ),
    HeadlessTokenController::class => create(HeadlessTokenController::class)
        ->constructor(
            get(\PaginiumCMS\Modules\Security\Services\ApiJwtService::class),
            get(SecurityAuditStore::class),
            get(JsonResponder::class)
        ),
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
        ->constructor(
            get(DemoStorageService::class),
            get(AuthenticationInterface::class),
            get(LoginAttemptTracker::class),
            get(SecurityLogger::class),
            get(JsonResponder::class)
        ),
    DemoResetScheduler::class => create(DemoResetScheduler::class)
        ->constructor(get(DemoMode::class), get(DemoStorageService::class)),
    RunDemoResetCommand::class => create(RunDemoResetCommand::class)
        ->constructor(get(DemoResetScheduler::class)),
    OriginPanelMode::class => create(OriginPanelMode::class),
    ProbeSupport::class => create(ProbeSupport::class),
    FeatureProbeRegistry::class => create(FeatureProbeRegistry::class)
        ->constructor(get(ProbeSupport::class)),
    ProjectCatalogReader::class => create(ProjectCatalogReader::class),
    CatalogDeployStatusResolver::class => create(CatalogDeployStatusResolver::class),
    ImplementationChecklistReader::class => create(ImplementationChecklistReader::class),
    OriginCatalogLabelResolver::class => create(OriginCatalogLabelResolver::class)
        ->constructor(get(SettingsRepositoryInterface::class)),
    ProjectCatalogMergeService::class => create(ProjectCatalogMergeService::class)
        ->constructor(
            get(ProjectCatalogReader::class),
            get(CatalogDeployStatusResolver::class),
            get(ImplementationChecklistReader::class),
            get(OriginCatalogLabelResolver::class),
        ),
    SetupStatusService::class => create(SetupStatusService::class)
        ->constructor(
            get(SettingsRepositoryInterface::class),
            get(UserRepository::class),
        ),
    SetupPreflightService::class => create(SetupPreflightService::class)
        ->constructor(
            __DIR__ . '/../../../storage',
            null,
        ),
    FirstAdminBootstrapService::class => create(FirstAdminBootstrapService::class)
        ->constructor(
            get(UserRepository::class),
            get(PasswordPolicyInterface::class),
        ),
    SetupController::class => create(SetupController::class)
        ->constructor(
            get(SetupStatusService::class),
            get(SetupPreflightService::class),
            get(FirstAdminBootstrapService::class),
            get(SettingsRepositoryInterface::class),
            get(Validator::class),
            get(JsonResponder::class),
        ),
    OriginController::class => create(OriginController::class)
        ->constructor(
            get(FeatureProbeRegistry::class),
            get(ProjectCatalogMergeService::class),
            get(OriginCatalogLabelResolver::class),
            get(HealthCheckManager::class),
            get(AdminCountsService::class),
            get(JsonResponder::class)
        ),
];
