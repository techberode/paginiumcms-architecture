<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Handlers;

use PaginiumCMS\Core\Content\Services\ContentPublishNotificationService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Scheduler\Handlers\ContentScheduledPublishHandler;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Support\IncidentNotifierTestFactory;
use PHPUnit\Framework\TestCase;

final class ContentScheduledPublishHandlerTest extends TestCase
{
    public function testSuccessMessageIncludesPublishedSlugs(): void
    {
        $service = $this->createMock(ContentScheduledPublishService::class);
        $service->method('publishDueItems')->willReturn([
            'published' => [
                ['type' => 'article', 'slug' => 'moj-clanok'],
            ],
            'skipped' => [],
            'diagnostics' => ['now' => '', 'queue_count' => 0, 'inspected' => []],
        ]);

        $handler = new ContentScheduledPublishHandler($service, $this->noopPublishNotifications());
        $result = $handler->handle();

        $this->assertTrue($result->success);
        $this->assertSame('Published 1 scheduled item(s): article/moj-clanok', $result->message);
    }

    private function noopPublishNotifications(): ContentPublishNotificationService
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['contentPublishNotifyEnabled' => false]);

        return new ContentPublishNotificationService(
            $settings,
            IncidentNotifierTestFactory::create($settings, $this->createMock(NotificationService::class)),
            $this->createMock(ContentRepositoryInterface::class)
        );
    }
}
