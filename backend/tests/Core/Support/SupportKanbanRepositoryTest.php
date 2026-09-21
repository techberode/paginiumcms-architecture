<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Support;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Support\Services\SupportKanbanRepository;
use PHPUnit\Framework\TestCase;

final class SupportKanbanRepositoryTest extends TestCase
{
    private const TEAM = 'team_aabbccddee';

    private string $baseDir;
    private SupportKanbanRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_kanban_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->repository = new SupportKanbanRepository(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testDefaultBoardAndTicketMove(): void
    {
        $board = $this->repository->getBoard(self::TEAM);
        $this->assertSame(SupportKanbanRepository::BOARD_SCHEMA, $board['schema']);
        $this->assertCount(3, $board['columns']);

        $ticket = $this->repository->createTicket(self::TEAM, [
            'subject' => 'Broken form',
            'body' => 'Contact form 500',
            'requesterEmail' => 'guest@example.com',
        ], ['agent_1']);

        $this->assertMatchesRegularExpression('/^tkt_[a-f0-9]{10}$/', $ticket['id']);
        $this->assertSame('open', $ticket['columnId']);
        $this->assertSame('guest@example.com', $ticket['requesterEmail']);

        $moved = $this->repository->updateTicket(self::TEAM, $ticket['id'], ['columnId' => 'pending'], ['agent_1']);
        $this->assertSame('pending', $moved['columnId']);

        $listed = $this->repository->listTickets(self::TEAM);
        $this->assertCount(1, $listed);
        $this->assertSame($ticket['id'], $listed[0]['id']);
    }

    public function testAssigneeMustBeSupportAgent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->createTicket(self::TEAM, [
            'subject' => 'Hello',
            'assigneeUserId' => 'stranger',
        ], ['agent_1']);
    }

    public function testBoardRejectsEmptyColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->saveBoard(self::TEAM, ['columns' => []]);
    }

    public function testDeletingColumnMovesTickets(): void
    {
        $this->repository->createTicket(self::TEAM, ['subject' => 'Keep'], []);
        $board = $this->repository->saveBoard(self::TEAM, [
            'columns' => [
                ['id' => 'inbox', 'label' => 'Inbox', 'color' => '#111111'],
            ],
            'labels' => [],
        ]);
        $this->assertSame('inbox', $board['columns'][0]['id']);
        $this->assertSame('inbox', $this->repository->listTickets(self::TEAM)[0]['columnId']);
    }

    public function testDueAtAndInternalNote(): void
    {
        $ticket = $this->repository->createTicket(self::TEAM, [
            'subject' => 'SLA',
            'dueAt' => 1_800_000_000,
        ], []);
        $this->assertSame(1_800_000_000, $ticket['dueAt']);
        $this->assertSame([], $ticket['internalNotes']);

        $withNote = $this->repository->addInternalNote(self::TEAM, $ticket['id'], 'Called the customer', 'user_agent');
        $this->assertCount(1, $withNote['internalNotes']);
        $this->assertSame('Called the customer', $withNote['internalNotes'][0]['body']);
        $this->assertSame('user_agent', $withNote['internalNotes'][0]['authorUserId']);
        $this->assertMatchesRegularExpression('/^nte_[a-f0-9]{10}$/', $withNote['internalNotes'][0]['id']);

        $cleared = $this->repository->updateTicket(self::TEAM, $ticket['id'], ['dueAt' => null], []);
        $this->assertNull($cleared['dueAt']);
        $this->assertCount(1, $cleared['internalNotes']);
    }

    public function testEmptyNoteIsRejected(): void
    {
        $ticket = $this->repository->createTicket(self::TEAM, ['subject' => 'Note'], []);
        $this->expectException(InvalidArgumentException::class);
        $this->repository->addInternalNote(self::TEAM, $ticket['id'], '   ', 'user_agent');
    }

    public function testColumnWipLimitBlocksExtraTicket(): void
    {
        $this->repository->saveBoard(self::TEAM, [
            'columns' => [
                ['id' => 'open', 'label' => 'Open', 'color' => '#2c7be5', 'wipLimit' => 1],
                ['id' => 'closed', 'label' => 'Closed', 'color' => '#10b981', 'wipLimit' => 0],
            ],
            'labels' => [],
        ]);
        $this->repository->createTicket(self::TEAM, ['subject' => 'One', 'columnId' => 'open'], []);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column WIP limit reached.');
        $this->repository->createTicket(self::TEAM, ['subject' => 'Two', 'columnId' => 'open'], []);
    }

    public function testStatsAfterTicketCompleted(): void
    {
        $ticket = $this->repository->createTicket(self::TEAM, ['subject' => 'SLA'], []);
        $this->repository->updateTicket(self::TEAM, $ticket['id'], ['columnId' => 'closed'], []);
        $stats = $this->repository->stats(self::TEAM);
        $this->assertSame(0, $stats['openCount']);
        $this->assertSame(1, $stats['completedCount']);
        $this->assertIsInt($stats['avgSeconds']);
    }

    public function testCannedRepliesRoundTrip(): void
    {
        $saved = $this->repository->saveCannedReplies(self::TEAM, [
            'replies' => [
                ['title' => 'Need logs', 'body' => 'Please attach the error log.'],
            ],
        ]);
        $this->assertSame(SupportKanbanRepository::CANNED_SCHEMA, $saved['schema']);
        $this->assertCount(1, $saved['replies']);
        $this->assertSame('Need logs', $saved['replies'][0]['title']);
        $this->assertMatchesRegularExpression('/^cnd_[a-f0-9]{10}$/', $saved['replies'][0]['id']);
        $this->assertSame('Need logs', $this->repository->getCannedReplies(self::TEAM)['replies'][0]['title']);
    }

    public function testLegacyScopeUsesGlobalPaths(): void
    {
        $legacy = SupportKanbanRepository::LEGACY_SCOPE;
        $board = $this->repository->getBoard($legacy);
        $this->assertSame(SupportKanbanRepository::BOARD_SCHEMA, $board['schema']);
        $this->repository->createTicket($legacy, ['subject' => 'Legacy ticket'], []);
        $this->assertCount(1, $this->repository->listTickets($legacy));
        $this->assertFileExists($this->baseDir . '/data/support-board.json');
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
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($path);
    }
}
