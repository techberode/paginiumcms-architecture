<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Security\Models\RoleRecord;
use PaginiumCMS\Modules\Security\Services\RoleRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class RoleRepositoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        vfsStream::setup('storage');
        $this->root = vfsStream::url('storage');
    }

    public function testSaveListAndDeleteCustomRole(): void
    {
        $repository = $this->repository();

        $record = $repository->save('MODERATOR', 'Moderator', ['content:view', 'content:edit']);
        $this->assertSame('MODERATOR', $record->id);
        $this->assertFalse($record->system);

        $items = $repository->list();
        $this->assertCount(1, $items);
        $this->assertSame('MODERATOR', $items[0]['id']);

        $repository->delete('MODERATOR');
        $this->assertSame([], $repository->list());
    }

    public function testSystemRoleCannotBeDeleted(): void
    {
        $repository = $this->repository();
        $repository->save('ADMIN', 'Administrator', ['user:manage'], true);

        $this->expectException(\RuntimeException::class);
        $repository->delete('ADMIN');
    }

    public function testRejectsReservedSuperAdminId(): void
    {
        $repository = $this->repository();

        $this->expectException(\RuntimeException::class);
        $repository->save('SUPER_ADMIN', 'Super', ['user:manage']);
    }

    public function testRejectsInvalidRoleId(): void
    {
        $repository = $this->repository();

        $this->expectException(\RuntimeException::class);
        $repository->save(RoleRecord::normalizeId('bad role'), 'Bad', ['content:view']);
    }

    private function repository(): RoleRepository
    {
        $validator = new FileValidator($this->root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        return new RoleRepository($reader, $writer);
    }
}
