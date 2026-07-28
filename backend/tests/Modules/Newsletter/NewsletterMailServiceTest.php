<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Newsletter;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Handlers\NewsletterWeeklyDigestHandler;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterLinkBuilder;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterSendStateStore;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken;
use PaginiumCMS\Support\Lang;
use PHPUnit\Framework\TestCase;

final class NewsletterMailServiceTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        Lang::resetForTests();
        $this->basePath = sys_get_temp_dir() . '/paginium-newsletter-mail-' . bin2hex(random_bytes(4));
        mkdir($this->basePath . '/data/newsletter', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->basePath);
        parent::tearDown();
    }

    public function testSendWeeklyDigestReturnsDisabledWhenSendOff(): void
    {
        $service = $this->makeService(
            newsletterSettings: ['sendEnabled' => false, 'weeklyDigestEnabled' => true],
            emailConfigured: true
        );

        $result = $service->sendWeeklyDigest();

        $this->assertSame(0, $result['sent']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertSame('send_disabled', $result['reason']);
    }

    public function testSendWeeklyDigestSendsToMatchingSubscribers(): void
    {
        $notifications = new NotificationService();
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('send')
            ->willReturn(true);
        $notifications->addAdapter('email', $adapter);

        $subscribers = $this->createMock(NewsletterRepositoryInterface::class);
        $subscribers->method('findActiveByPreference')
            ->with(NewsletterPreferences::WEEKLY_DIGEST)
            ->willReturn([
                ['id' => 'nl_digest_test', 'email' => 'digest@example.com'],
            ]);

        $article = $this->createMock(Article::class);
        $article->method('getDate')->willReturn(new \DateTimeImmutable('-1 day'));
        $article->method('getModifiedAt')->willReturn(time());
        $article->method('getTitle')->willReturn('Hello');
        $article->method('getSlug')->willReturn('hello');
        $article->method('getExcerpt')->willReturn('Preview');

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findAllArticles')
            ->with(['status' => 'published'])
            ->willReturn([$article]);

        $sendState = $this->sendStateStore();

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['newsletter', [
                'sendEnabled' => true,
                'weeklyDigestEnabled' => true,
                'sendBatchLimitPerRun' => 50,
            ]],
            ['general', ['siteName' => 'Test Site', 'siteUrl' => 'https://example.com']],
        ]);

        $service = new NewsletterMailService(
            $notifications,
            $settings,
            $subscribers,
            $content,
            $sendState,
            $this->linkBuilder()
        );

        $result = $service->sendWeeklyDigest();

        $this->assertSame(1, $result['sent']);
        $this->assertSame(0, $result['failed']);
        $this->assertNotNull($sendState->lastWeeklyDigestAt());
    }

    public function testSendNewArticleRespectsCooldown(): void
    {
        $notifications = new NotificationService();
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects($this->never())->method('send');
        $notifications->addAdapter('email', $adapter);

        $subscribers = $this->createMock(NewsletterRepositoryInterface::class);
        $subscribers->method('findActiveByPreference')
            ->willReturn([['email' => 'user@example.com']]);

        $article = $this->createMock(Article::class);
        $article->method('isPublished')->willReturn(true);
        $article->method('getTitle')->willReturn('News');
        $article->method('getSlug')->willReturn('news');
        $article->method('getExcerpt')->willReturn('Body');

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->with('news', 'article')->willReturn($article);

        $sendState = $this->sendStateStore();
        $sendState->markArticleSent('user@example.com');

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['newsletter', [
                'sendEnabled' => true,
                'newArticleEnabled' => true,
                'instantArticleCooldownHours' => 24,
                'sendBatchLimitPerRun' => 50,
            ]],
            ['general', ['siteName' => 'Test Site', 'siteUrl' => 'https://example.com']],
        ]);

        $service = new NewsletterMailService(
            $notifications,
            $settings,
            $subscribers,
            $content,
            $sendState,
            $this->linkBuilder()
        );

        $result = $service->sendNewArticleNotification('article', 'news');

        $this->assertSame('no_eligible_subscribers', $result['reason'] ?? '');
    }

    public function testWeeklyDigestHandlerTreatsNoArticlesAsSuccess(): void
    {
        $notifications = new NotificationService();
        $adapter = $this->createMock(AdapterInterface::class);
        $notifications->addAdapter('email', $adapter);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['newsletter', [
                'sendEnabled' => true,
                'weeklyDigestEnabled' => true,
            ]],
            ['general', ['siteName' => 'Test Site', 'siteUrl' => 'https://example.com']],
        ]);

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findAllArticles')
            ->with(['status' => 'published'])
            ->willReturn([]);

        $mail = new NewsletterMailService(
            $notifications,
            $settings,
            $this->createMock(NewsletterRepositoryInterface::class),
            $content,
            $this->sendStateStore(),
            $this->linkBuilder()
        );

        $handler = new NewsletterWeeklyDigestHandler($mail);
        $result = $handler->handle();

        $this->assertTrue($result->success);
        $this->assertStringContainsString('No new articles', $result->message);
    }

    /**
     * @param array<string, mixed> $newsletterSettings
     */
    private function makeService(
        array $newsletterSettings,
        bool $emailConfigured
    ): NewsletterMailService {
        $notifications = new NotificationService();
        if ($emailConfigured) {
            $adapter = $this->createMock(AdapterInterface::class);
            $notifications->addAdapter('email', $adapter);
        }

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['newsletter', $newsletterSettings],
            ['general', ['siteName' => 'Test Site']],
        ]);

        return new NewsletterMailService(
            $notifications,
            $settings,
            $this->createMock(NewsletterRepositoryInterface::class),
            $this->createMock(ContentRepositoryInterface::class),
            $this->sendStateStore(),
            $this->linkBuilder()
        );
    }

    private function linkBuilder(): NewsletterLinkBuilder
    {
        $settings = $this->createMock(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['siteUrl' => 'https://example.com']);

        return new NewsletterLinkBuilder(
            $settings,
            new NewsletterUnsubscribeToken('test-app-key-for-newsletter')
        );
    }

    private function sendStateStore(): NewsletterSendStateStore
    {
        $validator = new FileValidator($this->basePath);

        return new NewsletterSendStateStore(
            new FileReader($validator),
            new FileWriter($validator)
        );
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
