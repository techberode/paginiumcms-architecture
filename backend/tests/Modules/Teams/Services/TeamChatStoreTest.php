<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Teams\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Teams\Services\TeamChatStore;
use PaginiumCMS\Tests\Support\UploadPolicyEngineTestFactory;
use PHPUnit\Framework\TestCase;

final class TeamChatStoreTest extends TestCase
{
    private string $baseDir;
    private TeamRepository $teams;
    private TeamChatStore $chat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_tchat_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $this->teams = new TeamRepository($reader, $writer);
        $settings = $this->createMock(\PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) {
            if ($group === 'teamChat') {
                return [
                    'retentionDays' => 30,
                    'maxStoredMessages' => 800,
                    'liveWindowMessages' => 80,
                    'searchMaxResults' => 100,
                ];
            }

            return ['unifiedPolicyEnabled' => false];
        });
        $this->chat = new TeamChatStore(
            $reader,
            $writer,
            $this->teams,
            UploadPolicyEngineTestFactory::create($settings, $this->baseDir),
            $settings
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testMemberPostsAndDownloadsFile(): void
    {
        $member = new User();
        $member->setName('Ada');
        $member->setEmail('ada@example.com');
        $member->setRoles(['USER']);
        $stranger = new User();
        $stranger->setName('Eve');
        $stranger->setEmail('eve@example.com');
        $stranger->setRoles(['USER']);

        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, [$member->getId()]);
        $this->assertSame(
            [
                [
                    'id' => $team['id'],
                    'name' => 'Devs',
                    'type' => TeamRepository::TYPE_EXTERNAL,
                    'shared' => false,
                    'canManageHistory' => false,
                ],
            ],
            $this->chat->roomsFor($member)
        );
        $this->assertSame([], $this->chat->roomsFor($stranger));

        $posted = $this->chat->post((string) $team['id'], $member, TeamChatStore::KIND_CODE, 'echo 1;', 'php');
        $this->assertSame('code', $posted['kind']);
        $this->assertSame('php', $posted['language']);

        $attached = $this->chat->attach((string) $team['id'], $member, 'notes.md', "# Notes\n", 'text/markdown');
        $this->assertSame('file', $attached['kind']);
        $fileId = (string) ($attached['file']['id'] ?? '');
        $download = $this->chat->download((string) $team['id'], $fileId, $member);
        $this->assertNotNull($download);
        $this->assertSame("# Notes\n", $download['binary']);

        $this->expectException(InvalidArgumentException::class);
        $this->chat->messages((string) $team['id'], $stranger);
    }

    public function testSupportTeamChatDisabledByDefault(): void
    {
        $member = new User();
        $member->setName('Sam');
        $member->setEmail('sam@example.com');
        $member->setRoles(['USER']);

        $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, [$member->getId()]);
        $this->assertSame([], $this->chat->roomsFor($member));
    }

    public function testInternalTeamRoomWhenEnabled(): void
    {
        $member = new User();
        $member->setName('Sam');
        $member->setEmail('sam@example.com');
        $member->setRoles(['USER']);

        $support = $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, [$member->getId()]);
        $this->teams->update((string) $support['id'], ['teamChatEnabled' => true]);

        $enabledTeam = $this->teams->get((string) $support['id']);
        $this->assertNotNull($enabledTeam);
        $this->assertTrue($enabledTeam['teamChatEnabled']);
        $this->assertSame(TeamRepository::TYPE_SUPPORT, $enabledTeam['type']);

        $posted = $this->chat->post((string) $support['id'], $member, TeamChatStore::KIND_TEXT, 'SLA sync');
        $this->assertSame('SLA sync', $posted['body']);
        $this->assertCount(1, $this->chat->messages((string) $support['id'], $member));
    }

    public function testAdminWithoutMembershipHasNoRoom(): void
    {
        $admin = new User();
        $admin->setName('Root');
        $admin->setEmail('root@example.com');
        $admin->setRoles(['ADMIN']);

        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, ['user_other']);

        $this->assertSame([], $this->chat->roomsFor($admin));
        $this->expectException(InvalidArgumentException::class);
        $this->chat->messages((string) $team['id'], $admin);
    }

    public function testSharedRoomVisibleToGuestTeamOnlyWhenEnabled(): void
    {
        $owner = new User();
        $owner->setName('Support');
        $owner->setEmail('support@example.com');
        $owner->setRoles(['USER']);
        $guest = new User();
        $guest->setName('Dev');
        $guest->setEmail('dev@example.com');
        $guest->setRoles(['USER']);

        $support = $this->teams->create('Helpdesk', TeamRepository::TYPE_SUPPORT, [$owner->getId()]);
        $devs = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, [$guest->getId()]);
        $this->teams->update((string) $support['id'], ['teamChatEnabled' => true]);
        $this->teams->update((string) $devs['id'], ['teamChatEnabled' => true]);

        $beforeShare = $this->chat->roomsFor($guest);
        $this->assertCount(1, $beforeShare);
        $this->assertSame((string) $devs['id'], $beforeShare[0]['id']);

        $this->teams->update((string) $support['id'], [
            'teamChatShareEnabled' => true,
            'teamChatShareWithTeamIds' => [(string) $devs['id']],
        ]);

        $afterShare = $this->chat->roomsFor($guest);
        $this->assertCount(2, $afterShare);
        $supportRooms = array_values(array_filter(
            $afterShare,
            static fn (array $room): bool => ($room['id'] ?? '') === (string) $support['id']
        ));
        $this->assertNotEmpty($supportRooms);
        $supportRoom = $supportRooms[0];
        $this->assertTrue($supportRoom['shared']);

        $this->chat->post((string) $support['id'], $guest, TeamChatStore::KIND_TEXT, 'Cross-team ping');
    }

    public function testUserWithoutAnyTeamSeesNoRooms(): void
    {
        $loner = new User();
        $loner->setName('Lone');
        $loner->setEmail('lone@example.com');
        $loner->setRoles(['USER']);

        $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, ['user_other']);
        $this->assertSame([], $this->chat->roomsFor($loner));
    }

    public function testInboxCountsUnreadFromOtherMembers(): void
    {
        $alice = $this->userWithFixedId('user_alice_inbox');
        $alice->setName('Alice');
        $alice->setEmail('alice@example.com');
        $alice->setRoles(['USER']);
        $bob = $this->userWithFixedId('user_bob_inbox');
        $bob->setName('Bob');
        $bob->setEmail('bob@example.com');
        $bob->setRoles(['USER']);

        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, [$alice->getId(), $bob->getId()]);
        $this->assertCount(2, $team['memberUserIds']);
        $posted = $this->chat->post((string) $team['id'], $bob, TeamChatStore::KIND_TEXT, 'Ping from Bob');
        $this->assertSame($bob->getId(), $posted['authorUserId']);
        $this->assertCount(1, $this->chat->roomsFor($alice));

        $inbox = $this->chat->inboxFor($alice);
        $this->assertSame(1, $inbox['count']);
        $this->assertCount(1, $inbox['items']);
        $this->assertSame('Devs', $inbox['items'][0]['teamName']);
        $this->assertSame(1, $inbox['items'][0]['unread']);

        $this->chat->messages((string) $team['id'], $alice);
        $this->assertSame(0, $this->chat->inboxFor($alice)['count']);
    }

    public function testSearchExportAndImportRoundTrip(): void
    {
        $admin = $this->userWithFixedId('user_super_import');
        $admin->setName('Root');
        $admin->setEmail('root@example.com');
        $admin->setRoles(['SUPER_ADMIN']);
        $member = $this->userWithFixedId('user_member_chat');
        $member->setName('Ada');
        $member->setEmail('ada@example.com');
        $member->setRoles(['USER']);

        $team = $this->teams->create('Archive', TeamRepository::TYPE_EXTERNAL, [$member->getId(), $admin->getId()]);
        $this->teams->update((string) $team['id'], ['teamLeaderUserIds' => [$member->getId()]]);
        $posted = $this->chat->post((string) $team['id'], $member, TeamChatStore::KIND_TEXT, 'Retention export needle');

        $hits = $this->chat->search((string) $team['id'], $member, 'needle');
        $this->assertCount(1, $hits);
        $this->assertSame($posted['id'], $hits[0]['id']);

        $archive = $this->chat->exportArchive((string) $team['id'], $member);
        $this->assertStringContainsString('team-chat-export@1', $archive['json']);

        $payload = json_decode($archive['json'], true, 512, JSON_THROW_ON_ERROR);
        foreach ($this->messageFilesViaReflection((string) $team['id']) as $name) {
            unlink($this->baseDir . '/data/team-chat/' . $team['id'] . '/messages/' . $name);
        }
        $this->assertSame([], $this->chat->search((string) $team['id'], $member, 'needle'));

        $imported = $this->chat->importArchive((string) $team['id'], $admin, $payload);
        $this->assertSame(1, $imported);
        $this->assertCount(1, $this->chat->search((string) $team['id'], $member, 'needle'));
    }

    public function testMemberWithoutLeaderRoleCannotSearchHistory(): void
    {
        $member = $this->userWithFixedId('user_plain_member');
        $member->setName('Ada');
        $member->setEmail('ada@example.com');
        $member->setRoles(['USER']);

        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, [$member->getId()]);
        $this->chat->post((string) $team['id'], $member, TeamChatStore::KIND_TEXT, 'secret');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(TeamChatStore::HISTORY_ACCESS_DENIED);
        $this->chat->search((string) $team['id'], $member, 'secret');
    }

    public function testClearHistoryRequiresLeaderOrSuperAdmin(): void
    {
        $leader = $this->userWithFixedId('user_team_leader');
        $leader->setName('Lead');
        $leader->setEmail('lead@example.com');
        $leader->setRoles(['USER']);
        $member = $this->userWithFixedId('user_plain_clear');
        $member->setName('Ada');
        $member->setEmail('ada@example.com');
        $member->setRoles(['USER']);

        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, [$leader->getId(), $member->getId()]);
        $this->teams->update((string) $team['id'], ['teamLeaderUserIds' => [$leader->getId()]]);
        $this->chat->post((string) $team['id'], $member, TeamChatStore::KIND_TEXT, 'one');
        $this->chat->post((string) $team['id'], $leader, TeamChatStore::KIND_TEXT, 'two');

        try {
            $this->chat->clearHistory((string) $team['id'], $member);
            $this->fail('Expected history access denial for non-leader member.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(TeamChatStore::HISTORY_ACCESS_DENIED, $exception->getMessage());
        }

        $deleted = $this->chat->clearHistory((string) $team['id'], $leader);
        $this->assertSame(2, $deleted);
        $this->assertSame([], $this->chat->messages((string) $team['id'], $leader));
    }

    /**
     * @return list<string>
     */
    private function messageFilesViaReflection(string $teamId): array
    {
        $ref = new \ReflectionClass(TeamChatStore::class);
        $method = $ref->getMethod('messageFiles');

        return $method->invoke($this->chat, $teamId);
    }

    public function testDisabledTeamRoomBlocksAccess(): void
    {
        $member = new User();
        $member->setName('Ada');
        $member->setEmail('ada@example.com');
        $member->setRoles(['USER']);

        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, [$member->getId()]);
        $this->teams->update((string) $team['id'], ['teamChatEnabled' => false]);

        $this->assertSame([], $this->chat->roomsFor($member));
        $this->expectException(InvalidArgumentException::class);
        $this->chat->post((string) $team['id'], $member, TeamChatStore::KIND_TEXT, 'nope');
    }

    private function userWithFixedId(string $id): User
    {
        return new class($id) extends User {
            public function __construct(private string $fixedId)
            {
                parent::__construct();
            }

            public function getId(): string
            {
                return $this->fixedId;
            }
        };
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
