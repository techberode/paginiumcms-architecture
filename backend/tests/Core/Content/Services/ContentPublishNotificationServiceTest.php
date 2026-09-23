<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content\Services;

use PaginiumCMS\Core\Content\Services\ContentPublishNotificationService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\Lang;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PHPUnit\Framework\TestCase;

final class ContentPublishNotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Lang::resetForTests();
    }

    public function testScheduledPublishHookSendsForPageWhenEnabled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'monitoring') {
                return [
                    'contentPublishNotifyEnabled' => true,
                    'contentPublishConnector' => 'ntfy',
                    'contentPublishOnScheduled' => true,
                ];
            }
            if ($group === 'connectors') {
                return ['ntfyEnabled' => true, 'ntfyTopic' => 'test'];
            }

            return ['adminEmail' => 'admin@example.com'];
        });

        $notifications = $this->createMock(NotificationService::class);
        $notifications->method('getAdapters')->willReturn(['ntfy']);
        $notifications->expects($this->once())
            ->method('send')
            ->with(
                'ntfy',
                $this->anything(),
                $this->stringContains('Landing'),
                $this->stringContains('landing'),
                $this->callback(static function (array $options): bool {
                    return ($options['event'] ?? '') === 'content.publish.scheduled';
                })
            )
            ->willReturn(true);

        $page = (new Page())->setFrontMatter(['title' => 'Landing', 'slug' => 'landing']);
        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->with('landing', 'page')->willReturn($page);

        $service = new ContentPublishNotificationService(
            $settings,
            IncidentNotifierTestFactory::create($settings, $notifications),
            $content
        );
        $service->handleScheduledPublishHook([
            'type' => 'page',
            'slug' => 'landing',
            'scheduledAt' => '2026-09-23T09:00:00+02:00',
        ]);
    }

    public function testScheduledPublishHookIgnoredWhenDisabled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['contentPublishNotifyEnabled' => false]);

        $notifications = $this->createMock(NotificationService::class);
        $notifications->expects($this->never())->method('send');

        $content = $this->createMock(ContentRepositoryInterface::class);

        $service = new ContentPublishNotificationService(
            $settings,
            IncidentNotifierTestFactory::create($settings, $notifications),
            $content
        );
        $service->handleScheduledPublishHook(['type' => 'article', 'slug' => 'x']);
    }

    public function testManualPublishHookRespectsToggle(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'monitoring') {
                return [
                    'contentPublishNotifyEnabled' => true,
                    'contentPublishConnector' => 'email',
                    'contentPublishOnManual' => true,
                ];
            }
            if ($group === 'connectors') {
                return ['emailEnabled' => true];
            }

            return ['adminEmail' => 'ops@example.com'];
        });

        $notifications = $this->createMock(NotificationService::class);
        $notifications->method('getAdapters')->willReturn(['email']);
        $notifications->expects($this->once())
            ->method('send')
            ->with(
                'email',
                'ops@example.com',
                $this->anything(),
                $this->anything(),
                $this->callback(static function (array $options): bool {
                    return ($options['event'] ?? '') === 'content.publish.manual';
                })
            )
            ->willReturn(true);

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->willReturn(null);

        $service = new ContentPublishNotificationService(
            $settings,
            IncidentNotifierTestFactory::create($settings, $notifications),
            $content
        );
        $service->handleStatusChangeHook([
            'type' => 'page',
            'slug' => 'about',
            'status' => 'published',
            'previousStatus' => 'draft',
        ]);
    }

    public function testNotifySkippedItemsFiltersActionableReasons(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            if ($group === 'monitoring') {
                return [
                    'contentPublishNotifyEnabled' => true,
                    'contentPublishConnector' => 'email',
                    'contentPublishOnSkipped' => true,
                ];
            }
            if ($group === 'connectors') {
                return ['emailEnabled' => true];
            }

            return ['adminEmail' => 'ops@example.com'];
        });

        $notifications = $this->createMock(NotificationService::class);
        $notifications->method('getAdapters')->willReturn(['email']);
        $notifications->expects($this->once())
            ->method('send')
            ->with(
                'email',
                $this->anything(),
                $this->anything(),
                $this->stringContains('vip'),
                $this->callback(static function (array $options): bool {
                    return ($options['event'] ?? '') === 'content.publish.skipped'
                        && ($options['severity'] ?? '') === 'warning';
                })
            )
            ->willReturn(true);

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->willReturn(null);

        $service = new ContentPublishNotificationService(
            $settings,
            IncidentNotifierTestFactory::create($settings, $notifications),
            $content
        );
        $service->notifySkippedItems([
            ['type' => 'article', 'slug' => 'vip', 'reason' => 'otp_not_approved'],
            ['type' => 'page', 'slug' => 'wait', 'reason' => 'not_due'],
        ]);
    }
}
