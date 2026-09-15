<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Teams;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PHPUnit\Framework\TestCase;

final class TeamRepositoryTest extends TestCase
{
    private string $baseDir;
    private TeamRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_teams_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->repository = new TeamRepository(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testCreateListAndSupportPool(): void
    {
        $editorial = $this->repository->create('Newsroom', TeamRepository::TYPE_EDITORIAL, ['user_a', 'user_b']);
        $support = $this->repository->create('Helpdesk', TeamRepository::TYPE_SUPPORT, ['user_b', 'user_c']);

        $this->assertSame(TeamRepository::SCHEMA, $editorial['schema']);
        $this->assertMatchesRegularExpression('/^team_[a-f0-9]{10}$/', $editorial['id']);
        $this->assertSame(['user_a', 'user_b'], $editorial['memberUserIds']);

        $listed = $this->repository->list();
        $this->assertCount(2, $listed);

        $supportOnly = $this->repository->list(TeamRepository::TYPE_SUPPORT);
        $this->assertCount(1, $supportOnly);
        $this->assertSame($support['id'], $supportOnly[0]['id']);

        $this->assertSame(['user_b', 'user_c'], $this->repository->memberIdsForType(TeamRepository::TYPE_SUPPORT));
    }

    public function testUpdateAndDelete(): void
    {
        $team = $this->repository->create('Ops', TeamRepository::TYPE_OPS, ['user_1']);
        $updated = $this->repository->update($team['id'], [
            'name' => 'Platform ops',
            'memberUserIds' => ['user_1', 'user_2'],
        ]);

        $this->assertSame('Platform ops', $updated['name']);
        $this->assertSame(['user_1', 'user_2'], $updated['memberUserIds']);

        $this->repository->delete($team['id']);
        $this->assertNull($this->repository->get($team['id']));
        $this->assertSame([], $this->repository->list());
    }

    public function testRemoveUserFromAllTeams(): void
    {
        $team = $this->repository->create('Desk', TeamRepository::TYPE_SUPPORT, ['user_keep', 'user_gone']);
        $this->repository->removeUser('user_gone');

        $fresh = $this->repository->get($team['id']);
        $this->assertNotNull($fresh);
        $this->assertSame(['user_keep'], $fresh['memberUserIds']);
    }

    public function testRejectsInvalidTypeAndTraversalId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->create('X', 'hr', []);
    }

    public function testGetRejectsTraversalId(): void
    {
        $this->assertNull($this->repository->get('../settings'));
        $this->assertNull($this->repository->get('team_nothexxxxx'));
    }

    public function testNameIsRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->create('   ', TeamRepository::TYPE_CUSTOM, []);
    }

    public function testPresetTypeGetsDefaultNameWhenEmpty(): void
    {
        $team = $this->repository->create('', TeamRepository::TYPE_EDITORIAL, []);
        $this->assertSame('Editorial', $team['name']);
        $this->assertSame(TeamRepository::TYPE_EDITORIAL, $team['type']);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($path);
    }
}
