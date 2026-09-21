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
        $this->assertFalse($editorial['chatEnabled'] ?? true);
        $this->assertTrue($support['chatEnabled'] ?? false);
        $this->assertFalse($editorial['teamChatEnabled'] ?? true);
        $this->assertFalse($support['teamChatEnabled'] ?? true);
    }

    public function testIdsForMember(): void
    {
        $a = $this->repository->create('A', TeamRepository::TYPE_EDITORIAL, ['user_1']);
        $b = $this->repository->create('B', TeamRepository::TYPE_OPS, ['user_2']);
        $this->repository->update($a['id'], ['memberUserIds' => ['user_1', 'user_2']]);

        $this->assertSame([$a['id']], $this->repository->idsForMember('user_1'));
        $expected = [$a['id'], $b['id']];
        sort($expected);
        $this->assertSame($expected, $this->repository->idsForMember('user_2'));
        $this->assertSame([], $this->repository->idsForMember('user_none'));
    }

    public function testTeamChatShareTargetsPersist(): void
    {
        $owner = $this->repository->create('Desk', TeamRepository::TYPE_SUPPORT, []);
        $guest = $this->repository->create('Devs', TeamRepository::TYPE_EXTERNAL, ['user_1']);

        $updated = $this->repository->update($owner['id'], [
            'teamChatShareEnabled' => true,
            'teamChatShareWithTeamIds' => [$guest['id'], $owner['id'], 'team_invalid000'],
        ]);
        $this->assertTrue($updated['teamChatShareEnabled'] ?? false);
        $this->assertSame([$guest['id']], $updated['teamChatShareWithTeamIds'] ?? []);
    }

    public function testTeamLeadersMustBeMembers(): void
    {
        $team = $this->repository->create('Devs', TeamRepository::TYPE_EXTERNAL, ['user_a', 'user_b']);
        $updated = $this->repository->update($team['id'], [
            'teamLeaderUserIds' => ['user_b', 'user_outside', 'user_a'],
        ]);
        $this->assertSame(['user_a', 'user_b'], $updated['teamLeaderUserIds'] ?? []);

        $trimmed = $this->repository->update($team['id'], ['memberUserIds' => ['user_a']]);
        $this->assertSame(['user_a'], $trimmed['teamLeaderUserIds'] ?? []);
    }

    public function testTeamChatEnabledDefaultsAndUpdate(): void
    {
        $external = $this->repository->create('Partners', TeamRepository::TYPE_EXTERNAL, []);
        $this->assertTrue($external['teamChatEnabled'] ?? false);

        $support = $this->repository->create('Desk', TeamRepository::TYPE_SUPPORT, []);
        $this->assertFalse($support['teamChatEnabled'] ?? true);

        $enabled = $this->repository->update($support['id'], ['teamChatEnabled' => true]);
        $this->assertTrue($enabled['teamChatEnabled'] ?? false);

        $disabled = $this->repository->update($external['id'], ['teamChatEnabled' => false]);
        $this->assertFalse($disabled['teamChatEnabled'] ?? true);
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

    public function testPersistsReplyMailboxToggle(): void
    {
        $team = $this->repository->create('Helpdesk', TeamRepository::TYPE_SUPPORT, []);
        $this->assertFalse($team['replyMailEnabled'] ?? true);
        $this->assertSame('', $team['replyMail'] ?? 'x');

        $updated = $this->repository->update($team['id'], [
            'replyMailEnabled' => true,
            'replyMail' => ' Support@CMS.EXAMPLE.COM ',
        ]);
        $this->assertTrue($updated['replyMailEnabled'] ?? false);
        $this->assertSame('support@cms.example.com', $updated['replyMail'] ?? '');
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

    public function testCreatesExternalType(): void
    {
        $team = $this->repository->create('Web development', TeamRepository::TYPE_EXTERNAL, ['user_dev']);
        $this->assertSame('Web development', $team['name']);
        $this->assertSame(TeamRepository::TYPE_EXTERNAL, $team['type']);
        $this->assertSame('#2563eb', $this->repository->update((string) $team['id'], ['color' => '#2563EB'])['color']);
        $this->assertSame(['user_dev'], $team['memberUserIds']);
        $this->assertContains(TeamRepository::TYPE_EXTERNAL, $this->repository->types());
    }

    public function testExternalTypeRequiresName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->create('', TeamRepository::TYPE_EXTERNAL, []);
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
