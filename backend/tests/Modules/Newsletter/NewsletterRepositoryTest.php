<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Newsletter;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterRepository;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken;
use PHPUnit\Framework\TestCase;

final class NewsletterRepositoryTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/paginium-newsletter-' . bin2hex(random_bytes(4));
        mkdir($this->basePath . '/data/newsletter', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->basePath);
    }

    public function testSubscribeAndExportCsv(): void
    {
        $repo = $this->repository();

        $created = $repo->subscribe(
            'User@Example.com',
            'footer',
            [NewsletterPreferences::WEEKLY_DIGEST, NewsletterPreferences::GENERAL_NEWS]
        );
        $this->assertTrue($created['created']);
        $this->assertSame('user@example.com', $created['email']);
        $this->assertFalse($created['pending']);

        $duplicate = $repo->subscribe(
            'user@example.com',
            'footer',
            [NewsletterPreferences::NEW_ARTICLE]
        );
        $this->assertFalse($duplicate['created']);
        $this->assertTrue($duplicate['merged']);

        $csv = $repo->exportCsv();
        $this->assertStringContainsString('user@example.com', $csv);
        $this->assertStringStartsWith('id,email,subscribed_at,source,preferences,status,consent_at,unsubscribed_at', $csv);
    }

    public function testFindActiveByPreferenceFiltersStatusAndKey(): void
    {
        $repo = $this->repository();

        $repo->subscribe('digest@example.com', 'footer', [NewsletterPreferences::WEEKLY_DIGEST]);
        $repo->subscribe('article@example.com', 'footer', [NewsletterPreferences::NEW_ARTICLE]);

        $weekly = $repo->findActiveByPreference(NewsletterPreferences::WEEKLY_DIGEST);
        $this->assertCount(1, $weekly);
        $this->assertSame('digest@example.com', $weekly[0]['email']);
    }

    public function testDoubleOptInCreatesPendingSubscriberAndConfirmActivates(): void
    {
        $repo = $this->repository();

        $created = $repo->subscribe(
            'pending@example.com',
            'footer',
            [NewsletterPreferences::WEEKLY_DIGEST],
            null,
            true
        );

        $this->assertTrue($created['pending']);
        $this->assertSame('pending', $created['status']);
        $this->assertIsString($created['confirmToken']);

        $this->assertSame([], $repo->findActiveByPreference(NewsletterPreferences::WEEKLY_DIGEST));

        $confirmed = $repo->confirmByToken($created['confirmToken']);
        $this->assertTrue($confirmed['ok']);
        $activeAfter = $repo->findActiveByPreference(NewsletterPreferences::WEEKLY_DIGEST);
        $this->assertNotEmpty($activeAfter);
        $this->assertSame('pending@example.com', $activeAfter[0]['email']);
    }

    public function testUnsubscribeByTokenMarksSubscriberUnsubscribed(): void
    {
        $repo = $this->repository();
        $created = $repo->subscribe('leave@example.com', 'footer', [NewsletterPreferences::GENERAL_NEWS]);
        $token = (new NewsletterUnsubscribeToken('test-key'))->forSubscriber($created['id']);

        $result = $repo->unsubscribeByToken($token);
        $this->assertTrue($result['ok']);
        $this->assertSame([], $repo->findActiveByPreference(NewsletterPreferences::GENERAL_NEWS));

        $again = $repo->unsubscribeByToken($token);
        $this->assertTrue($again['ok']);
        $this->assertSame('already_unsubscribed', $again['reason'] ?? '');
    }

    public function testConfirmRejectsInvalidToken(): void
    {
        $repo = $this->repository();
        $result = $repo->confirmByToken('not-a-valid-token');
        $this->assertFalse($result['ok']);
    }

    private function repository(): NewsletterRepository
    {
        $validator = new FileValidator($this->basePath);

        return new NewsletterRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new NewsletterUnsubscribeToken('test-key')
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
