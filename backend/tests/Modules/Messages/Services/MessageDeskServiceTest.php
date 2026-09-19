<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Messages\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageDeskService;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Messages\Services\MessageRoutingStore;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class MessageDeskServiceTest extends TestCase
{
    private string $baseDir;
    private MessageDeskService $desk;
    private MessageRoutingStore $routing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_msg_desk_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $this->routing = new MessageRoutingStore($reader, $writer);
        $this->desk = new MessageDeskService(
            new MessageRepository($reader, $writer),
            $this->routing,
            new TeamRepository($reader, $writer),
            $this->createStub(UserRepository::class)
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testIngestRoutesAndClaimsFlock(): void
    {
        $assignee = new User();
        $assignee->setName('Ada');
        $assignee->setEmail('ada@example.com');
        $other = new User();
        $other->setName('Bob');
        $other->setEmail('bob@example.com');

        $this->routing->save([
            'enabled' => true,
            'routes' => [[
                'subject' => 'Technická podpora',
                'enabled' => true,
                'teamIds' => [],
                'userIds' => [$assignee->getId()],
            ]],
        ]);

        $incoming = new ContactMessage('Visitor', 'guest@example.com', 'Notebook sa nespustí po aktualizácii.');
        $incoming->setSubject('Technická podpora');
        $result = $this->desk->ingest($incoming);
        $this->assertFalse($result['appended']);
        $this->assertSame([$assignee->getId()], $result['message']->getAssigneeUserIds());

        $this->assertTrue($this->desk->claim($result['message'], $assignee));
        $this->assertSame(ContactMessage::STATUS_IN_PROGRESS, $result['message']->getHandleStatus());
        $this->assertFalse($this->desk->claim($result['message'], $other));

        $this->assertTrue($this->desk->reply($result['message'], $assignee, 'Skús reštart v safe mode.'));
        $this->assertCount(1, $result['message']->getThread());
        $this->assertSame('staff', $result['message']->getThread()[0]['authorType']);
    }

    public function testSecondVisitorMessageAppendsToOpenThread(): void
    {
        $first = new ContactMessage('Visitor', 'guest@example.com', 'Prvá správa z formulára.');
        $first->setSubject('Všeobecný dotaz');
        $created = $this->desk->ingest($first);

        $second = new ContactMessage('Visitor', 'guest@example.com', 'Dopĺňam ešte sériové číslo zariadenia.');
        $second->setSubject('Všeobecný dotaz');
        $again = $this->desk->ingest($second);

        $this->assertTrue($again['appended']);
        $this->assertSame($created['message']->getId(), $again['message']->getId());
        $this->assertCount(1, $again['message']->getThread());
        $this->assertSame('visitor', $again['message']->getThread()[0]['authorType']);
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
