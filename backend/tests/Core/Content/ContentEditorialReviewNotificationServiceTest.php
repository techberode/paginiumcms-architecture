<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\Services\ContentEditorialReviewNotificationService;
use PaginiumCMS\Core\Content\Services\ContentEditorialReviewService;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Support\Lang;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PHPUnit\Framework\TestCase;

final class ContentEditorialReviewNotificationServiceTest extends TestCase
{
    private string $baseDir = '';
    private TeamRepository $teams;

    protected function setUp(): void
    {
        parent::setUp();
        Lang::resetForTests();

        $this->baseDir = sys_get_temp_dir() . '/pag_editorial_notify_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->teams = new TeamRepository(new FileReader($validator), new FileWriter($validator));

        $team = $this->teams->create('News', TeamRepository::TYPE_EDITORIAL, ['leader-1']);
        $this->teams->update((string) $team['id'], ['teamLeaderUserIds' => ['leader-1']]);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testNotifiesOnTransitionToPendingReview(): void
    {
        $settings = $this->settingsMock(notifyLeaders: true, publishNotify: true);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->method('getAdapters')->willReturn(['email']);
        $notifications->expects($this->once())
            ->method('send')
            ->with(
                'email',
                $this->anything(),
                $this->stringContains('Hello'),
                $this->stringContains('hello'),
                $this->callback(static function (array $options): bool {
                    return ($options['event'] ?? '') === 'content.editorial.pending_review';
                })
            )
            ->willReturn(true);

        $service = $this->service($settings, $notifications);

        $service->handleAfterSave([
            'status' => ContentEditorialReviewService::STATUS_PENDING,
            'previousStatus' => 'draft',
            'type' => 'article',
            'slug' => 'hello',
            'title' => 'Hello',
        ]);
    }

    public function testSkipsWhenAlreadyPending(): void
    {
        $settings = $this->settingsMock(notifyLeaders: true, publishNotify: true);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->never())->method('send');

        $service = $this->service($settings, $notifications);

        $service->handleAfterSave([
            'status' => ContentEditorialReviewService::STATUS_PENDING,
            'previousStatus' => ContentEditorialReviewService::STATUS_PENDING,
            'type' => 'article',
            'slug' => 'hello',
            'title' => 'Hello',
        ]);
    }

    private function service(
        SettingsRepositoryInterface $settings,
        NotificationService $notifications
    ): ContentEditorialReviewNotificationService {
        $plans = $this->createMock(ProjectPlanRepositoryInterface::class);
        $plans->method('findAll')->willReturn([]);

        $review = new ContentEditorialReviewService($settings, $this->teams, $plans);

        return new ContentEditorialReviewNotificationService(
            $settings,
            $this->teams,
            IncidentNotifierTestFactory::create($settings, $notifications),
            $review
        );
    }

    private function settingsMock(bool $notifyLeaders, bool $publishNotify): SettingsRepositoryInterface
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) use (
            $notifyLeaders,
            $publishNotify
        ): array {
            if ($group === 'content') {
                return [
                    'editorialReviewEnabled' => true,
                    'editorialReviewNotifyLeaders' => $notifyLeaders,
                ];
            }
            if ($group === 'monitoring') {
                return [
                    'contentPublishNotifyEnabled' => $publishNotify,
                    'contentPublishConnector' => 'email',
                    'alertsEnabled' => true,
                ];
            }
            if ($group === 'connectors') {
                return ['emailEnabled' => true];
            }

            return ['adminEmail' => 'admin@example.com'];
        });

        return $settings;
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($path);
    }
}
