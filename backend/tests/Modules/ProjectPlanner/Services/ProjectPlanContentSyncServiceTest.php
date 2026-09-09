<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\ProjectPlanner\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\ProjectPlanner\Repositories\ProjectPlanRepository;
use PaginiumCMS\Modules\ProjectPlanner\Services\ProjectPlanContentSyncService;
use PaginiumCMS\Modules\ProjectPlanner\Services\ProjectPlanDocumentValidator;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class ProjectPlanContentSyncServiceTest extends TestCase
{
    private ProjectPlanRepository $repository;
    private ProjectPlanContentSyncService $sync;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $this->repository = new ProjectPlanRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new ProjectPlanDocumentValidator(),
        );
        $this->sync = new ProjectPlanContentSyncService($this->repository);
    }

    public function testPublishMarksLinkedItemDone(): void
    {
        $this->repository->create([
            'id' => 'relaunch-sync',
            'title' => 'Relaunch',
            'timezone' => 'UTC',
            'items' => [
                [
                    'id' => 'item-announcement',
                    'title' => 'Launch announcement',
                    'contentType' => 'article',
                    'status' => 'in_progress',
                    'linkedContent' => ['type' => 'article', 'slug' => 'hello-world'],
                    'notes' => '',
                ],
                [
                    'id' => 'item-other',
                    'title' => 'Unrelated page',
                    'contentType' => 'page',
                    'status' => 'planned',
                    'linkedContent' => ['type' => 'page', 'slug' => 'about'],
                    'notes' => '',
                ],
            ],
        ]);

        $this->sync->handleStatusChange([
            'type' => 'article',
            'slug' => 'hello-world',
            'status' => 'published',
            'previousStatus' => 'draft',
            'completedAt' => '2026-09-09T10:00:00+00:00',
        ]);

        $plan = $this->repository->findById('relaunch-sync');
        $this->assertNotNull($plan);
        $this->assertSame('done', $plan->items[0]->status);
        $this->assertSame('2026-09-09T10:00:00+00:00', $plan->items[0]->completedAt);
        $this->assertSame('planned', $plan->items[1]->status);
    }

    public function testAlreadyDoneItemIsNotRewritten(): void
    {
        $this->repository->create([
            'id' => 'done-plan',
            'title' => 'Done plan',
            'timezone' => 'UTC',
            'items' => [
                [
                    'id' => 'item-done',
                    'title' => 'Already shipped',
                    'contentType' => 'page',
                    'status' => 'done',
                    'linkedContent' => ['type' => 'page', 'slug' => 'home'],
                    'completedAt' => '2026-01-01T00:00:00+00:00',
                    'notes' => '',
                ],
            ],
        ]);

        $this->sync->handleAfterSave([
            'type' => 'pages',
            'slug' => 'home',
            'status' => 'published',
            'completedAt' => '2026-09-09T12:00:00+00:00',
        ]);

        $plan = $this->repository->findById('done-plan');
        $this->assertNotNull($plan);
        $this->assertSame('2026-01-01T00:00:00+00:00', $plan->items[0]->completedAt);
    }

    public function testScheduledPublishMarksLinkedItem(): void
    {
        $this->repository->create([
            'id' => 'scheduled-plan',
            'title' => 'Scheduled',
            'timezone' => 'UTC',
            'items' => [
                [
                    'id' => 'item-news',
                    'title' => 'News',
                    'contentType' => 'article',
                    'status' => 'planned',
                    'linkedContent' => ['type' => 'article', 'slug' => 'weekly'],
                    'notes' => '',
                ],
            ],
        ]);

        $this->sync->handleScheduledPublish([
            'type' => 'article',
            'slug' => 'weekly',
            'scheduledAt' => '2026-09-09T08:00:00+00:00',
        ]);

        $plan = $this->repository->findById('scheduled-plan');
        $this->assertNotNull($plan);
        $this->assertSame('done', $plan->items[0]->status);
        $this->assertNotNull($plan->items[0]->completedAt);
    }

    public function testUnpublishedStatusIsIgnored(): void
    {
        $this->repository->create([
            'id' => 'draft-plan',
            'title' => 'Draft',
            'timezone' => 'UTC',
            'items' => [
                [
                    'id' => 'item-draft',
                    'title' => 'Draft page',
                    'contentType' => 'page',
                    'status' => 'planned',
                    'linkedContent' => ['type' => 'page', 'slug' => 'contact'],
                    'notes' => '',
                ],
            ],
        ]);

        $this->sync->handleStatusChange([
            'type' => 'page',
            'slug' => 'contact',
            'status' => 'draft',
            'previousStatus' => 'published',
        ]);

        $plan = $this->repository->findById('draft-plan');
        $this->assertNotNull($plan);
        $this->assertSame('planned', $plan->items[0]->status);
    }
}
