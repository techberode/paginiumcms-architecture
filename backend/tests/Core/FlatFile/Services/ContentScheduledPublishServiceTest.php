<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use DateTimeImmutable;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Services\ContentScheduledPublishService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class ContentScheduledPublishServiceTest extends TestCase
{
    public function testPublishDueItemsPublishesScheduledContent(): void
    {
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $slug = 'scheduled-page-' . uniqid('', true);
        $dueAt = (new DateTimeImmutable('-1 minute'))->format('c');

        $page = new Page();
        $page->setSlug($slug);
        $page->setTitle('Scheduled page');
        $page->setContent('# Scheduled');
        $page->setStatus('scheduled');
        $page->setFrontMatter([
            'title' => 'Scheduled page',
            'slug' => $slug,
            'status' => 'scheduled',
            'scheduledAt' => $dueAt,
            'publishApprovedAt' => $dueAt,
        ]);
        $repo->save($page);

        $service = $this->app->getContainer()->get(ContentScheduledPublishService::class);
        $result = $service->publishDueItems(new DateTimeImmutable('now'));

        $publishedSlugs = array_column($result['published'], 'slug');
        $this->assertContains($slug, $publishedSlugs);

        $saved = $repo->findBySlug($slug, 'page');
        $this->assertNotNull($saved);
        $this->assertSame('published', $saved->getStatus());
        $this->assertNull($saved->getScheduledAt());
    }

    public function testPublishDueItemsSkipsFutureSchedule(): void
    {
        $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
        $slug = 'future-page-' . uniqid('', true);
        $futureAt = (new DateTimeImmutable('+1 hour'))->format('c');

        $page = new Page();
        $page->setSlug($slug);
        $page->setTitle('Future page');
        $page->setContent('# Future');
        $page->setStatus('scheduled');
        $page->setFrontMatter([
            'title' => 'Future page',
            'slug' => $slug,
            'status' => 'scheduled',
            'scheduledAt' => $futureAt,
            'publishApprovedAt' => $futureAt,
        ]);
        $repo->save($page);

        $service = $this->app->getContainer()->get(ContentScheduledPublishService::class);
        $result = $service->publishDueItems(new DateTimeImmutable('now'));

        $this->assertSame([], $result['published']);
        $this->assertSame([], $result['skipped']);

        $saved = $repo->findBySlug($slug, 'page');
        $this->assertNotNull($saved);
        $this->assertSame('scheduled', $saved->getStatus());
    }

    public function testPublishDueItemsSkipsWhenOtpEnabledWithoutApproval(): void
    {
        $settings = $this->app->getContainer()->get(SettingsRepositoryInterface::class);
        $originalWorkflows = $settings->group('workflows');

        try {
            $settings->setGroup('workflows', array_merge($originalWorkflows, [
                'publishApprovalOtpEnabled' => true,
            ]));

            $repo = $this->app->getContainer()->get(ContentRepositoryInterface::class);
            $slug = 'otp-blocked-' . uniqid('', true);
            $dueAt = (new DateTimeImmutable('-1 minute'))->format('c');

            $page = new Page();
            $page->setSlug($slug);
            $page->setTitle('OTP blocked');
            $page->setContent('# OTP');
            $page->setStatus('scheduled');
            $page->setFrontMatter([
                'title' => 'OTP blocked',
                'slug' => $slug,
                'status' => 'scheduled',
                'scheduledAt' => $dueAt,
            ]);
            $repo->save($page);

            $service = $this->app->getContainer()->get(ContentScheduledPublishService::class);
            $result = $service->publishDueItems(new DateTimeImmutable('now'));

            $publishedSlugs = array_column($result['published'], 'slug');
            $this->assertNotContains($slug, $publishedSlugs);

            $skippedForSlug = array_values(array_filter(
                $result['skipped'],
                static fn (array $row): bool => ($row['slug'] ?? '') === $slug && ($row['type'] ?? '') === 'page'
            ));
            $this->assertCount(1, $skippedForSlug);
            $this->assertSame('otp_not_approved', $skippedForSlug[0]['reason']);

            $saved = $repo->findBySlug($slug, 'page');
            $this->assertNotNull($saved);
            $this->assertSame('scheduled', $saved->getStatus());
        } finally {
            $settings->setGroup('workflows', $originalWorkflows);
        }
    }
}
