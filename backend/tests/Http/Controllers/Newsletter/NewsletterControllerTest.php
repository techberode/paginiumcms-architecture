<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Newsletter;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken;
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
            'enabledPreferences' => implode("\n", NewsletterPreferences::DEFAULT_ENABLED),
            'requireConsentCheckbox' => false,
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

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function subscribePayload(string $email, array $extra = []): array
    {
        return array_merge([
            'email' => $email,
            'preferences' => NewsletterPreferences::DEFAULT_ENABLED,
        ], $extra);
    }

    public function testFooterSubscribeRequiresEnabledSetting(): void
    {
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload(
                $this->uniqueEmail('disabled')
            ))
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
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload(
                $this->uniqueEmail('footer-user')
            ))
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['created']);
    }

    public function testFooterSubscribeRequiresAtLeastOnePreference(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => true,
        ]);

        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', [
                'email' => $this->uniqueEmail('no-prefs'),
                'preferences' => [],
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testFooterSubscribeMergesPreferencesOnDuplicate(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => true,
            'enabledPreferences' => implode("\n", [
                NewsletterPreferences::WEEKLY_DIGEST,
                NewsletterPreferences::NEW_ARTICLE,
                NewsletterPreferences::GENERAL_NEWS,
            ]),
        ]);

        $email = $this->uniqueEmail('merge');
        $first = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload($email, [
                'preferences' => [NewsletterPreferences::WEEKLY_DIGEST],
            ]))
        );
        $this->assertSame(201, $first->getStatusCode());

        $second = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload($email, [
                'preferences' => [NewsletterPreferences::NEW_ARTICLE],
            ]))
        );
        $data = $this->getJsonResponse($second);

        $this->assertSame(200, $second->getStatusCode());
        $this->assertFalse($data['data']['created']);
        $this->assertTrue($data['data']['merged']);

        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $list = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/subscribers')
        );
        $listData = $this->getJsonResponse($list);
        $row = null;
        foreach ($listData['data']['items'] as $item) {
            if ($item['email'] === strtolower($email)) {
                $row = $item;
                break;
            }
        }

        $this->assertNotNull($row);
        $this->assertContains(NewsletterPreferences::WEEKLY_DIGEST, $row['preferences']);
        $this->assertContains(NewsletterPreferences::NEW_ARTICLE, $row['preferences']);
    }

    public function testFooterSubscribeUsesGenericSuccessMessageForDuplicate(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => true,
        ]);

        $email = $this->uniqueEmail('generic');
        $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload($email))
        );

        $second = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload($email))
        );
        $data = $this->getJsonResponse($second);

        $this->assertStringNotContainsString('already', strtolower((string) ($data['message'] ?? '')));
    }

    public function testFooterSubscribeDeduplicatesEmail(): void
    {
        $this->setSettingsGroup('newsletter', [
            'footerEnabled' => true,
        ]);

        $email = $this->uniqueEmail('dup');
        $payload = $this->subscribePayload($email);
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
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload($email, [
                '_hp' => 'http://spam.example',
            ]))
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
            $this->createJsonRequest('POST', '/api/newsletter/subscribe', $this->subscribePayload($email))
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
            $this->createJsonRequest('POST', '/api/maintenance/newsletter', $this->subscribePayload($email, [
                'source' => 'coming_soon',
            ]))
        );

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/subscribers')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $sources = array_column($data['data']['items'], 'source');
        $this->assertContains('coming_soon', $sources);
        $this->assertContains(strtolower($email), array_column($data['data']['items'], 'email'));
    }

    public function testMaintenanceHoneypotDoesNotPersistSubscriber(): void
    {
        $login = $this->loginAsAdminUser();
        $this->assertSame(200, $login['response']->getStatusCode());

        $this->setSettingsGroup('maintenance', [
            'mode' => 'coming_soon',
            'newsletterEnabled' => true,
        ]);

        $email = $this->uniqueEmail('maintenance-bot');
        $response = $this->handleRequest(
            $this->createJsonRequest('POST', '/api/maintenance/newsletter', $this->subscribePayload($email, [
                'source' => 'coming_soon',
                '_hp' => 'filled-by-bot',
            ]))
        );
        $this->assertSame(201, $response->getStatusCode());

        $list = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/admin/newsletter/subscribers')
        );
        $data = $this->getJsonResponse($list);

        $this->assertNotContains(strtolower($email), array_column($data['data']['items'], 'email'));
    }

    public function testConfirmActivatesPendingSubscriber(): void
    {
        $repo = $this->container()->get(\PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class);
        $created = $repo->subscribe(
            $this->uniqueEmail('confirm-me'),
            'footer',
            [NewsletterPreferences::WEEKLY_DIGEST],
            null,
            true
        );
        $this->assertTrue($created['pending']);
        $token = (string) $created['confirmToken'];

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/newsletter/confirm?token=' . urlencode($token))
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['confirmed']);
    }

    public function testUnsubscribeMarksSubscriberInactive(): void
    {
        $email = $this->uniqueEmail('unsub-me');
        $repo = $this->container()->get(\PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface::class);
        $created = $repo->subscribe($email, 'footer', [NewsletterPreferences::GENERAL_NEWS]);
        $token = $this->container()
            ->get(NewsletterUnsubscribeToken::class)
            ->forSubscriber($created['id']);

        $response = $this->handleRequest(
            $this->createJsonRequest('GET', '/api/newsletter/unsubscribe?token=' . urlencode($token))
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['unsubscribed']);
    }
}
