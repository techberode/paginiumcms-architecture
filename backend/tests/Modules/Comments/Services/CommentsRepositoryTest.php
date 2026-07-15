<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Comments\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class CommentsRepositoryTest extends TestCase
{
    private CommentsRepository $repository;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->repository = new CommentsRepository($reader, $writer);
    }

    public function testSaveFindAndFilter(): void
    {
        $comment = new Comment('hello-world', 'Jane', 'Nice article');
        $comment->setEmail('jane@example.com');
        $comment->setStatus(Comment::STATUS_APPROVED);
        $this->repository->save($comment);

        $found = $this->repository->findById($comment->getId());
        $this->assertNotNull($found);
        $this->assertSame('hello-world', $found->getArticleSlug());

        $approved = $this->repository->findAll(['status' => Comment::STATUS_APPROVED]);
        $this->assertCount(1, $approved);
    }

    public function testDeleteMissingThrows(): void
    {
        $this->expectException(FlatFileException::class);
        $this->repository->delete('missing');
    }
}
