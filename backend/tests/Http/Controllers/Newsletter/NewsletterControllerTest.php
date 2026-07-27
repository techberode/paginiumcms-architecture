<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Newsletter;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class NewsletterControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetNewsletterSettings();
        $this->clearNewsletterSubscribers();
    }

    /**
     * @param array<string, mixed> $values
     */
    private function setSettingsGroup(string $group, array $values): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup($group, array_merge($settings->group($group), $values));
    }

    private function resetNewsletterSettings(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => false,
        ]);
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

    private function uniqueEmail(string $prefix): string
    {
        return $prefix . '_' . uniqid('', true) . '@example.com';
    }

    public function testFooterSubscribeRequiresEnabledSetting(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', [
                'email' => $this->uniqueEmail('disabled'),
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testFooterSubscribeCreatesSubscriberWhenEnabled(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => true,
            'footerHint' => 'Join us',
        ]);

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', [
                'email' => $this->uniqueEmail('footer-user'),
            ])
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['created']);
    }

    public function testFooterSubscribeDeduplicatesEmail(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => true,
        ]);

        $email = $this->uniqueEmail('dup');
        $payload = ['email' => $email];
        $first = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $payload)
        );
        $this->assertSame(201, $first->getStatusCode());

        $second = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $payload)
        );
        $data = $this->getJsonResponse($second);

        $this->assertSame(200, $second->getStatusCode());
        $this->assertFalse($data['data']['created']);
    }

    public function testHoneypotReturnsFakeSuccessWithoutPersisting(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => true,
        ]);

        $email = $this->uniqueEmail('bot');
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', [
                'email' => $email,
                '_hp' => 'http://spam.example',
            ])
        );
        $this->assertSame(201, $response->getStatusCode());

        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $list = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/subscribers')
        );
        $data = $this->getJsonResponse($list);

        $emails = array_column($data['data']['items'], 'email');
        $this->assertNotContains($email, $emails);
    }

    public function testAdminCanListSubscribers(): void
    {
        $this->setSettingsGroup('newsletter', ['footerEnabled' => true]);

        $email = $this->uniqueEmail('admin-list');
        $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', [
                'email' => $email,
            ])
        );

        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/subscribers')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertGreaterThanOrEqual(1, $data['data']['count']);
        $this->assertArrayHasKey('bySource', $data['data']);
        $this->assertContains($email, array_column($data['data']['items'], 'email'));
    }

    public function testAdminListRequiresAuth(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/subscribers')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testMaintenanceSubscriberVisibleInAdminList(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $this->setSettingsGroup('maintenance', [
            'mode' => 'coming_soon',
            'newsletterEnabled' => true,
        ]);

        $email = $this->uniqueEmail('maintenance-user');
        $this->handleRequest(
            $this->createJsonRequest('POST', '/api/maintenance/newsletter', [
                'email' => $email,
                'source' => 'coming_soon',
            ])
        );

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/subscribers')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $sources = array_column($data['data']['items'], 'source');
        $this->assertContains('coming_soon', $sources);
        $this->assertContains($email, array_column($data['data']['items'], 'email'));
    }
}
