<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class TrashServiceTest extends TestCase
{
    private TrashService $trashService;
    private FileWriter $fileWriter;
    private string $contentRoot;

    protected function setUp(): void
    {
        $structure = [
            'content' => [
                'pages' => [],
                'trash' => [],
            ],
        ];

        vfsStream::setup('storage', null, $structure);
        $this->contentRoot = vfsStream::url('storage/content');

        $validator = new FileValidator($this->contentRoot);
        $reader = new FileReader($validator);
        $this->fileWriter = new FileWriter($validator);
        $this->trashService = new TrashService($reader);
    }

    public function testListItemsReturnsEmptyWhenTrashMissing(): void
    {
        $this->assertSame([], $this->trashService->listItems());
    }

    public function testListAndRestoreAfterSoftDelete(): void
    {
        $relative = 'pages/restore-me.md';
        $this->fileWriter->write($relative, "# Restore me\n");

        $this->fileWriter->delete($relative);

        $items = $this->trashService->listItems();
        $this->assertCount(1, $items);
        $this->assertSame($relative, $items[0]['originalPath']);
        $this->assertNotSame('', $items[0]['id']);

        $restoredPath = $this->trashService->restore($items[0]['id']);
        $this->assertSame($relative, $restoredPath);
        $this->assertFileExists($this->contentRoot . '/pages/restore-me.md');
        $this->assertSame([], $this->trashService->listItems());
    }

    public function testRestoreUnknownIdThrows(): void
    {
        $this->expectException(FlatFileException::class);
        $this->trashService->restore('missing-id');
    }

    public function testRestoreFailsWhenDestinationExists(): void
    {
        $relative = 'pages/conflict.md';
        $this->fileWriter->write($relative, "# Original\n");
        $this->fileWriter->delete($relative);
        file_put_contents($this->contentRoot . '/pages/conflict.md', '# Recreated');

        $items = $this->trashService->listItems();
        $this->expectException(FlatFileException::class);
        $this->trashService->restore($items[0]['id']);
    }
}
