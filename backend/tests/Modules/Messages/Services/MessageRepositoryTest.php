<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Messages\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class MessageRepositoryTest extends TestCase
{
    private MessageRepository $repository;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->repository = new MessageRepository($reader, $writer);
    }

    public function testSaveFindDeleteMessage(): void
    {
        $message = new ContactMessage('John Doe', 'john@example.com', 'Hello from contact form');
        $message->setSubject('Support');
        $message->setPriority(ContactMessage::PRIORITY_HIGH);
        $message->markProcessed(true);
        $this->repository->save($message);

        $found = $this->repository->findById($message->getId());
        $this->assertNotNull($found);
        $this->assertSame('Support', $found->getSubject());
        $this->assertSame(ContactMessage::PRIORITY_HIGH, $found->getPriority());
        $this->assertTrue($found->isProcessed());

        $serialized = $found->jsonSerialize();
        $this->assertArrayHasKey('isArchived', $serialized);
        $this->assertArrayHasKey('priority', $serialized);

        $all = $this->repository->findAll();
        $this->assertCount(1, $all);

        $this->repository->delete($message->getId());
        $this->assertNull($this->repository->findById($message->getId()));
    }

    public function testDeleteMissingThrows(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->delete('missing');
    }
}
