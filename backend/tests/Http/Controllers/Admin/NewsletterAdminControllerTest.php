<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PaginiumCMS\Tests\Http\TestCase;

final class NewsletterAdminControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearNewsletterSubscribers();
        $this->resetNewsletterSettings();
    }

    public function testSendStatusRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/send/status')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testSendStatusReturnsConfiguredFlags(): void
    {
        $this->loginAsAdminUser();
        $this->wireEmailAdapter();

        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('newsletter', array_merge($settings->group('newsletter'), [
            'sendEnabled' => true,
            'weeklyDigestEnabled' => true,
            'newArticleEnabled' => false,
        ]));

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/send/status')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['configured']);
        $this->assertTrue($data['data']['sendEnabled']);
        $this->assertTrue($data['data']['weeklyDigestEnabled']);
        $this->assertFalse($data['data']['newArticleEnabled']);
    }

    public function testSendWeeklyDigestRequiresSuperAdmin(): void
    {
        $this->loginAsAdminUser();
        $this->wireEmailAdapter();
        $this->enableSending();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/newsletter/send/weekly-digest')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSendWeeklyDigestReturnsNoArticlesWhenEmpty(): void
    {
        $this->loginAsSuperAdminUser();
        $this->wireEmailAdapter();
        $this->enableSending();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/newsletter/send/weekly-digest')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertContains($data['data']['reason'] ?? '', ['no_articles', 'no_subscribers']);
    }

    public function testSendTestRequiresValidEmail(): void
    {
        $this->loginAsSuperAdminUser();
        $this->wireEmailAdapter();

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/newsletter/send/test', [
                'email' => 'not-an-email',
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testSendTestSendsWhenConfigured(): void
    {
        $this->loginAsSuperAdminUser();
        $this->wireEmailAdapter(true);

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/admin/newsletter/send/test', [
                'email' => 'admin@example.com',
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
    }

    private function wireEmailAdapter(bool $sendOk = false): void
    {
        $notifications = $this->container()->get(NotificationService::class);
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('send')->willReturn($sendOk);
        $notifications->addAdapter('email', $adapter);
    }

    private function enableSending(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('newsletter', array_merge($settings->group('newsletter'), [
            'sendEnabled' => true,
            'weeklyDigestEnabled' => true,
            'newArticleEnabled' => true,
            'enabledPreferences' => implode("\n", NewsletterPreferences::ALL),
        ]));
    }

    private function resetNewsletterSettings(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('newsletter', array_merge($settings->group('newsletter'), [
            'footerEnabled' => false,
            'sendEnabled' => false,
            'weeklyDigestEnabled' => false,
            'newArticleEnabled' => false,
            'enabledPreferences' => implode("\n", NewsletterPreferences::DEFAULT_ENABLED),
        ]));
    }

    private function clearNewsletterSubscribers(): void
    {
        $reader = $this->container()->get(FileReaderInterface::class);
        $writer = $this->container()->get(FileWriterInterface::class);
        $path = 'data/newsletter/subscribers.json';

        if ($reader->exists($path)) {
            $writer->write($path, '[]', false);
        }
    }
}
