<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\ProjectPlanner\Repositories;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Modules\ProjectPlanner\Repositories\ProjectPlanRepository;
use PaginiumCMS\Modules\ProjectPlanner\Services\ProjectPlanDocumentValidator;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class ProjectPlanRepositoryTest extends TestCase
{
    private ProjectPlanRepository $repository;

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
    }

    public function testCreateListUpdateAndDelete(): void
    {
        $plan = $this->repository->create([
            'id' => 'site-relaunch-2026',
            'title' => 'Corporate site relaunch',
            'description' => 'Q4 marketing launch',
            'timezone' => 'Europe/Bratislava',
            'createdBy' => 'user-uuid',
            'isDefault' => true,
            'phases' => [
                ['id' => 'phase-1', 'title' => 'Content draft', 'sortOrder' => 1],
            ],
            'items' => [
                [
                    'id' => 'item-1',
                    'phaseId' => 'phase-1',
                    'title' => 'Launch announcement',
                    'contentType' => 'article',
                    'dueAt' => '2026-09-15T09:00:00+02:00',
                    'status' => 'planned',
                    'linkedContent' => ['type' => 'article', 'slug' => null],
                    'completedAt' => null,
                    'notes' => '',
                ],
            ],
        ]);

        $this->assertSame('site-relaunch-2026', $plan->id);
        $this->assertTrue($plan->isDefault);
        $this->assertCount(1, $plan->phases);
        $this->assertCount(1, $plan->items);
        $this->assertSame('article', $plan->items[0]->contentType);

        $loaded = $this->repository->findById('site-relaunch-2026');
        $this->assertNotNull($loaded);
        $this->assertSame('Corporate site relaunch', $loaded->title);

        $updated = $this->repository->update('site-relaunch-2026', [
            'title' => 'Corporate relaunch',
        ]);
        $this->assertSame('Corporate relaunch', $updated->title);
        $this->assertSame($plan->createdAt, $updated->createdAt);

        $all = $this->repository->findAll();
        $this->assertCount(1, $all);
        $this->assertSame('site-relaunch-2026', $this->repository->findDefault()?->id);

        $this->repository->delete('site-relaunch-2026');
        $this->assertNull($this->repository->findById('site-relaunch-2026'));
        $this->assertSame([], $this->repository->findAll());
    }

    public function testRejectsPathTraversalIds(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->repository->create([
            'id' => '../etc/passwd',
            'title' => 'Hostile',
            'timezone' => 'UTC',
        ]);
    }

    public function testRejectsSlashInIdBeforeAnyWrite(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->repository->findById('foo/bar');
    }

    public function testRejectsUnknownStatus(): void
    {
        $this->expectException(ValidationException::class);
        $this->repository->create([
            'id' => 'bad-status-plan',
            'title' => 'Bad status',
            'timezone' => 'UTC',
            'items' => [
                [
                    'id' => 'item-1',
                    'title' => 'Nope',
                    'contentType' => 'page',
                    'status' => 'published',
                ],
            ],
        ]);
    }

    public function testRejectsUnknownContentType(): void
    {
        $this->expectException(ValidationException::class);
        $this->repository->create([
            'id' => 'bad-type-plan',
            'title' => 'Bad type',
            'timezone' => 'UTC',
            'items' => [
                [
                    'id' => 'item-1',
                    'title' => 'Nope',
                    'contentType' => 'podcast',
                    'status' => 'planned',
                ],
            ],
        ]);
    }

    public function testRejectsPhaseIdThatDoesNotExist(): void
    {
        $this->expectException(ValidationException::class);
        $this->repository->create([
            'id' => 'orphan-phase',
            'title' => 'Orphan',
            'timezone' => 'UTC',
            'phases' => [
                ['id' => 'phase-1', 'title' => 'One', 'sortOrder' => 1],
            ],
            'items' => [
                [
                    'id' => 'item-1',
                    'phaseId' => 'missing-phase',
                    'title' => 'Nope',
                    'contentType' => 'page',
                    'status' => 'planned',
                ],
            ],
        ]);
    }

    public function testDuplicateCreateIsRejected(): void
    {
        $this->repository->create([
            'id' => 'once-only',
            'title' => 'First',
            'timezone' => 'UTC',
        ]);

        $this->expectException(ValidationException::class);
        $this->repository->create([
            'id' => 'once-only',
            'title' => 'Second',
            'timezone' => 'UTC',
        ]);
    }

    public function testOnlyOneDefaultPlanRemains(): void
    {
        $this->repository->create([
            'id' => 'plan-a',
            'title' => 'A',
            'timezone' => 'UTC',
            'isDefault' => true,
        ]);
        $this->repository->create([
            'id' => 'plan-b',
            'title' => 'B',
            'timezone' => 'UTC',
            'isDefault' => true,
        ]);

        $this->assertFalse($this->repository->findById('plan-a')?->isDefault);
        $this->assertTrue($this->repository->findById('plan-b')?->isDefault);
        $this->assertSame('plan-b', $this->repository->findDefault()?->id);
    }

    public function testDeleteMissingThrows(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->delete('missing-plan');
    }

    public function testRenameOnUpdateIsRejected(): void
    {
        $this->repository->create([
            'id' => 'keep-id',
            'title' => 'Keep',
            'timezone' => 'UTC',
        ]);

        $this->expectException(ValidationException::class);
        $this->repository->update('keep-id', ['id' => 'new-id']);
    }

    public function testAddUpdateAndDeleteItem(): void
    {
        $this->repository->create([
            'id' => 'item-plan',
            'title' => 'Items',
            'timezone' => 'UTC',
            'phases' => [
                ['id' => 'phase-1', 'title' => 'One', 'sortOrder' => 1],
            ],
        ]);

        $added = $this->repository->addItem('item-plan', [
            'title' => 'Homepage',
            'contentType' => 'page',
            'phaseId' => 'phase-1',
        ]);
        $this->assertCount(1, $added->items);
        $itemId = $added->items[0]->id;
        $this->assertSame('planned', $added->items[0]->status);

        $updated = $this->repository->updateItem('item-plan', $itemId, ['status' => 'done']);
        $this->assertSame('done', $updated->items[0]->status);
        $this->assertNotNull($updated->items[0]->completedAt);

        $cleared = $this->repository->deleteItem('item-plan', $itemId);
        $this->assertSame([], $cleared->items);
    }
}
