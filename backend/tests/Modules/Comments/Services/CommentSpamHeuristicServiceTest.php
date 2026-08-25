<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Comments\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Services\CommentSpamHeuristicService;
use PaginiumCMS\Modules\Comments\Services\CommentSubmissionVelocityStore;
use PaginiumCMS\Modules\Comments\Services\DisposableEmailDomainList;
use PHPUnit\Framework\TestCase;

final class CommentSpamHeuristicServiceTest extends TestCase
{
    private CommentSpamHeuristicService $service;

    private CommentSubmissionVelocityStore $velocityStore;

    private DisposableEmailDomainList $domains;

    private string $domainFile = '';

    private string $velocityFile = '';

    protected function setUp(): void
    {
        DisposableEmailDomainList::resetCacheForTesting();

        $this->domainFile = sys_get_temp_dir() . '/paginium_disposable_domains_' . uniqid('', true) . '.txt';
        file_put_contents($this->domainFile, "mailinator.com\ntempmail.com\n");
        $this->domains = new DisposableEmailDomainList($this->domainFile);

        $this->velocityFile = sys_get_temp_dir() . '/paginium_comment_velocity_' . uniqid('', true) . '.json';
        $fixedNow = strtotime('2026-08-25T12:30:00+00:00');
        $this->velocityStore = new CommentSubmissionVelocityStore(
            $this->velocityFile,
            static fn (): int => $fixedNow
        );

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('comments')->willReturn([
            'spamHeuristicsEnabled' => true,
            'spamMaxLinks' => 2,
            'spamVelocityMaxPerHour' => 5,
            'spamQuarantineThreshold' => 50,
            'spamRejectThreshold' => 80,
        ]);

        $this->service = new CommentSpamHeuristicService($settings, $this->domains, $this->velocityStore);
    }

    protected function tearDown(): void
    {
        DisposableEmailDomainList::resetCacheForTesting();

        if ($this->domainFile !== '' && is_file($this->domainFile)) {
            unlink($this->domainFile);
        }

        if ($this->velocityFile !== '' && is_file($this->velocityFile)) {
            unlink($this->velocityFile);
        }
    }

    public function testLegitCommentIsAllowed(): void
    {
        $verdict = $this->service->evaluate([
            'author' => 'Jane Reader',
            'email' => 'jane@example.com',
            'content' => 'Great article, thanks for sharing!',
            '_hp' => '',
        ], '203.0.113.10');

        $this->assertTrue($verdict->isAllow());
    }

    public function testHoneypotFilledIsSilentReject(): void
    {
        $verdict = $this->service->evaluate([
            'author' => 'Bot',
            'content' => 'spam',
            '_hp' => 'http://spam.example',
        ], '203.0.113.11');

        $this->assertTrue($verdict->isRejectSilent());
    }

    public function testDisposableEmailQuarantinesComment(): void
    {
        $verdict = $this->service->evaluate([
            'author' => 'Spammer',
            'email' => 'bot@mailinator.com',
            'content' => 'Buy now! http://a.com http://b.com http://c.com',
            '_hp' => '',
        ], '203.0.113.12');

        $this->assertTrue($verdict->isQuarantine());
        $this->assertGreaterThanOrEqual(50, $verdict->score);
    }

    public function testManyLinksRejectsComment(): void
    {
        $verdict = $this->service->evaluate([
            'author' => 'Spammer',
            'email' => 'bot@mailinator.com',
            'content' => 'http://a.com http://b.com http://c.com http://d.com http://e.com',
            '_hp' => '',
        ], '203.0.113.13');

        $this->assertTrue($verdict->isReject());
    }

    public function testVelocityExceededAddsScore(): void
    {
        $ip = '203.0.113.14';
        $clientHash = hash('sha256', $ip);

        for ($i = 0; $i < 5; $i++) {
            $this->velocityStore->record($clientHash);
        }

        $this->assertGreaterThanOrEqual(
            5,
            $this->velocityStore->countRecent($clientHash, 1),
            'Velocity store must persist submissions before evaluate()'
        );

        $verdict = $this->service->evaluate([
            'author' => 'Fast poster',
            'email' => 'fast@mailinator.com',
            'content' => 'Another comment',
            '_hp' => '',
        ], $ip);

        $this->assertTrue($verdict->isQuarantine(), 'Expected quarantine when velocity + disposable exceed threshold');
        $this->assertGreaterThanOrEqual(50, $verdict->score);
    }
}
