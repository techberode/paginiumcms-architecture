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
        $board = $this->repository->getBoard();
        $this->assertSame(SupportKanbanRepository::BOARD_SCHEMA, $board['schema']);
        $this->assertCount(3, $board['columns']);

        $ticket = $this->repository->createTicket([
            'subject' => 'Broken form',
            'body' => 'Contact form 500',
            'requesterEmail' => 'guest@example.com',
        ], ['agent_1']);

        $this->assertMatchesRegularExpression('/^tkt_[a-f0-9]{10}$/', $ticket['id']);
        $this->assertSame('open', $ticket['columnId']);
        $this->assertSame('guest@example.com', $ticket['requesterEmail']);

        $moved = $this->repository->updateTicket($ticket['id'], ['columnId' => 'pending'], ['agent_1']);
        $this->assertSame('pending', $moved['columnId']);

        $listed = $this->repository->listTickets();
        $this->assertCount(1, $listed);
        $this->assertSame($ticket['id'], $listed[0]['id']);
    }

    public function testAssigneeMustBeSupportAgent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->createTicket([
            'subject' => 'Hello',
            'assigneeUserId' => 'stranger',
        ], ['agent_1']);
    }

    public function testBoardRejectsEmptyColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->saveBoard(['columns' => []]);
    }

    public function testDeletingColumnMovesTickets(): void
    {
        $this->repository->createTicket(['subject' => 'Keep'], []);
        $board = $this->repository->saveBoard([
            'columns' => [
                ['id' => 'inbox', 'label' => 'Inbox', 'color' => '#111111'],
            ],
            'labels' => [],
        ]);
        $this->assertSame('inbox', $board['columns'][0]['id']);
        $this->assertSame('inbox', $this->repository->listTickets()[0]['columnId']);
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
