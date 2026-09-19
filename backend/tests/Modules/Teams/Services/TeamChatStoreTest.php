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
        $settings = $this->createConfiguredMock(
            \PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface::class,
            ['group' => ['unifiedPolicyEnabled' => false]]
        );
        $this->chat = new TeamChatStore(
            $reader,
            $writer,
            $this->teams,
            UploadPolicyEngineTestFactory::create($settings, $this->baseDir)
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
        $this->assertSame([['id' => $team['id'], 'name' => 'Devs']], $this->chat->roomsFor($member));
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
