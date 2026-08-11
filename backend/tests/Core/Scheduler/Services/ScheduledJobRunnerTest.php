<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Services;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Conflict\Contracts\ConflictLoggerInterface;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Logging\Contracts\LogWriterInterface;
use PaginiumCMS\Core\Monitoring\Services\FlatFileStatsCollector;
use PaginiumCMS\Core\Monitoring\Services\LogIncidentScanner;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportBuilder;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportScheduler;
use PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler;
use PaginiumCMS\Core\Monitoring\Services\SchedulerStateStore;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService;
use PaginiumCMS\Core\Scheduler\Handlers\BackupScheduledHandler;
use PaginiumCMS\Core\Scheduler\Handlers\ContentScheduledPublishHandler;
use PaginiumCMS\Core\Scheduler\Handlers\MonitoringPipelineHandler;
use PaginiumCMS\Core\Scheduler\Handlers\SystemDeployHandler;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Scheduler\Handlers\WebhookDeliveryHandler;
use PaginiumCMS\Core\Webhooks\Services\WebhookDeliveryService;
use PaginiumCMS\Core\Webhooks\Services\WebhookDeliveryStore;
use PaginiumCMS\Core\Webhooks\Services\WebhookRegistryStore;
use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Handlers\NewsletterWeeklyDigestHandler;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterLinkBuilder;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterSendStateStore;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken;
use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;
use PaginiumCMS\Core\Scheduler\Services\CronExpressionEvaluator;
use PaginiumCMS\Core\Scheduler\Services\JobHandlerRegistry;
use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\Scheduler\Services\JobRunStore;
use PaginiumCMS\Core\Scheduler\Services\ScheduledJobRunner;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Support\GitPublishTestHelper;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PHPUnit\Framework\TestCase;

final class ScheduledJobRunnerTest extends TestCase
{
    /** @var array<string, string> */
    private array $files = [];

    public function testRunDueSkipsWhenMasterSwitchOff(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('scheduler')->willReturn(['enabled' => false]);

        $runner = $this->makeRunner($settings);

        $result = $runner->runDue();
        $this->assertSame(0, $result['executed']);
    }

    public function testRunJobByIdExecutesBackupHandler(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $backup = $this->createMock(BackupInterface::class);
        $backup->method('runScheduledBackupIfDue')->willReturn(['ran' => false, 'reason' => 'not_due']);

        $runner = $this->makeRunner($settings, $backup);
        $result = $runner->runJobById('backup-scheduled');

        $this->assertSame('backup-scheduled', $result['job_id']);
        $this->assertSame('Backup not due', $result['message']);
        $this->assertFalse($result['success']);
        $this->assertSame('skipped', $result['outcome']);
    }

    public function testRunJobByIdSurvivesRunLogWriteFailure(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $backup = $this->createMock(BackupInterface::class);
        $backup->method('runScheduledBackupIfDue')->willReturn(['ran' => false, 'reason' => 'not_due']);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $reader->method('getBasePath')->willReturn('/tmp/paginium-test');

        $writer = $this->createMock(FileWriterInterface::class);
        $writer->method('write')->willThrowException(new \RuntimeException('Permission denied'));
        $writer->method('createDirectory')->willReturnCallback(static function (): void {
        });

        $registry = new JobRegistryStore($reader, $writer);
        $runs = new JobRunStore($reader, $writer, $registry);

        $scheduledPublish = $this->createMock(ContentScheduledPublishService::class);
        $systemDeploy = new SystemDeployHandler(new SystemDeployService($settings));
        $handlers = new JobHandlerRegistry(
            new BackupScheduledHandler($backup),
            new MonitoringPipelineHandler($this->buildMonitoringScheduler()),
            new ContentScheduledPublishHandler($scheduledPublish),
            $systemDeploy,
            new NewsletterWeeklyDigestHandler($this->makeNewsletterMailService($settings)),
            GitPublishTestHelper::disabledHandler($reader, $writer, $settings),
            $this->webhookDeliveryHandler()
        );

        $runner = new ScheduledJobRunner(
            $settings,
            $registry,
            $runs,
            $handlers,
            new CronExpressionEvaluator()
        );

        $result = $runner->runJobById('backup-scheduled');

        $this->assertSame('backup-scheduled', $result['job_id']);
        $this->assertFalse($result['run_log_persisted']);
        $this->assertStringContainsString('Permission denied', (string) ($result['run_log_error'] ?? ''));
    }

    private function makeRunner(
        SettingsRepositoryInterface $settings,
        ?BackupInterface $backup = null
    ): ScheduledJobRunner {
        [$registry, $runs, $reader, $writer] = $this->makeStores();

        $backup ??= $this->createMock(BackupInterface::class);

        $scheduledPublish = $this->createMock(ContentScheduledPublishService::class);
        $scheduledPublish->method('publishDueItems')->willReturn(['published' => [], 'skipped' => []]);
        $systemDeploy = new SystemDeployHandler(new SystemDeployService($settings));

        $handlers = new JobHandlerRegistry(
            new BackupScheduledHandler($backup),
            new MonitoringPipelineHandler($this->buildMonitoringScheduler()),
            new ContentScheduledPublishHandler($scheduledPublish),
            $systemDeploy,
            new NewsletterWeeklyDigestHandler($this->makeNewsletterMailService($settings)),
            GitPublishTestHelper::disabledHandler($reader, $writer, $settings),
            $this->webhookDeliveryHandler()
        );

        return new ScheduledJobRunner(
            $settings,
            $registry,
            $runs,
            $handlers,
            new CronExpressionEvaluator()
        );
    }

    /**
     * @return array{0: JobRegistryStore, 1: JobRunStore, 2: FileReaderInterface, 3: FileWriterInterface}
     */
    private function makeStores(): array
    {
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturnCallback(fn (string $path): bool => isset($this->files[$path]));
        $reader->method('read')->willReturnCallback(fn (string $path): string => $this->files[$path] ?? '');
        $reader->method('getBasePath')->willReturn('/tmp/paginium-test');

        $writer = $this->createMock(FileWriterInterface::class);
        $writer->method('write')->willReturnCallback(function (string $path, string $content): void {
            $this->files[$path] = $content;
        });
        $writer->method('createDirectory')->willReturnCallback(static function (): void {
        });

        $registry = new JobRegistryStore($reader, $writer);
        $runs = new JobRunStore($reader, $writer, $registry);

        return [$registry, $runs, $reader, $writer];
    }

    private function buildMonitoringScheduler(): MonitoringScheduler
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([
            'reportsEnabled' => false,
            'notifyLogErrors' => false,
            'notifyLogWarnings' => false,
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturn(false);
        $fileWriter = $this->createMock(FileWriterInterface::class);

        $state = new SchedulerStateStore($reader, $fileWriter);
        $reporter = $this->createMock(ReporterInterface::class);
        $health = $this->createMock(HealthCheckManager::class);
        $flatFile = new FlatFileStatsCollector(
            $this->createMock(ContentRepositoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->createMock(BackupInterface::class),
            $this->createMock(TrashService::class),
            $this->createMock(LockManagerInterface::class),
            $this->createMock(ConflictLoggerInterface::class)
        );
        $builder = new MonitoringReportBuilder($settings, $reporter, $health, $flatFile);
        $reportScheduler = new MonitoringReportScheduler(
            $settings,
            $builder,
            IncidentNotifierTestFactory::create($settings, $this->createMock(NotificationService::class)),
            $state
        );
        $logScanner = new LogIncidentScanner(
            $settings,
            $this->createMock(LogWriterInterface::class),
            IncidentNotifierTestFactory::create($settings, $this->createMock(NotificationService::class)),
            $state
        );

        return new MonitoringScheduler($reportScheduler, $logScanner);
    }

    private function makeNewsletterMailService(SettingsRepositoryInterface $settings): NewsletterMailService
    {
        $basePath = sys_get_temp_dir() . '/paginium-scheduler-newsletter-' . bin2hex(random_bytes(4));
        mkdir($basePath . '/data/newsletter', 0777, true);
        $validator = new FileValidator($basePath);

        return new NewsletterMailService(
            new NotificationService(),
            $settings,
            $this->createMock(NewsletterRepositoryInterface::class),
            $this->createMock(ContentRepositoryInterface::class),
            new NewsletterSendStateStore(
                new FileReader($validator),
                new FileWriter($validator)
            ),
            new NewsletterLinkBuilder(
                $settings,
                new NewsletterUnsubscribeToken('test-key')
            )
        );
    }

    private function webhookDeliveryHandler(): WebhookDeliveryHandler
    {
        vfsStream::setup('wh-scheduler-test', null, ['data' => ['webhooks' => []]]);
        $root = vfsStream::url('wh-scheduler-test');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $encryption = new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=');
        $registry = new WebhookRegistryStore($reader, $encryption);
        $deliveries = new WebhookDeliveryStore($reader);

        return new WebhookDeliveryHandler(
            new WebhookDeliveryService($registry, $deliveries),
            $deliveries
        );
    }
}
