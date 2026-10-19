<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Support\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Support\Services\TeamKanbanAccess;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Team kanban routes carry only AuthMiddleware; membership is the authorization boundary (ISS-204).
 */
final class TeamKanbanAccessTest extends TestCase
{
    private string $baseDir;
    private TeamRepository $teams;
    private TeamKanbanAccess $access;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_tkanban_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->teams = new TeamRepository(new FileReader($validator), new FileWriter($validator));
        $this->access = new TeamKanbanAccess($this->teams);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testMemberCanAccessSupportTeamBoard(): void
    {
        $member = $this->user('USER');
        $team = $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, [$member->getId()]);

        $this->assertSame((string) $team['id'], (string) $this->access->requireAccess((string) $team['id'], $member)['id']);
    }

    public function testAdminWithoutMembershipIsDenied(): void
    {
        $admin = $this->user('ADMIN');
        $team = $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, ['user_other']);

        $this->assertSame([], $this->access->listAccessibleTeams($admin));
        $this->expectException(InvalidArgumentException::class);
        $this->access->requireAccess((string) $team['id'], $admin);
    }

    public function testSuperAdminCanAccessAnyEnabledBoard(): void
    {
        $root = $this->user('SUPER_ADMIN');
        $team = $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, ['user_other']);

        $this->assertTrue($this->access->canAccess($team, $root));
        $this->assertTrue($this->access->canManageBoard($team, $root));
    }

    public function testDisabledBoardIsDeniedEvenForMembers(): void
    {
        $member = $this->user('USER');
        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, [$member->getId()]);

        $this->expectException(InvalidArgumentException::class);
        $this->access->requireAccess((string) $team['id'], $member);
    }

    public function testUnknownTeamIsDenied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->access->requireAccess('team_missing', $this->user('SUPER_ADMIN'));
    }

    public function testMemberIsNotBoardManagerUnlessLeader(): void
    {
        $member = $this->user('EDITOR');
        $team = $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, [$member->getId()]);

        $this->assertFalse($this->access->canManageBoard($team, $member));
    }

    private function user(string $role): User
    {
        $user = new User();
        $user->setName($role);
        $user->setRoles([$role]);

        return $user;
    }

    private function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            /** @var \SplFileInfo $item */
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
