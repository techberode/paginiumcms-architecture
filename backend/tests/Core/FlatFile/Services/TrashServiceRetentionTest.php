<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Services;

use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\TrashService;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class TrashServiceRetentionTest extends TestCase
{
    public function testPurgeOlderThanRemovesOnlyStaleItems(): void
    {
        $root = vfsStream::setup('storage');
        $trash = vfsStream::newDirectory('trash')->at($root);

        $oldMeta = [
            'id' => 'old-item',
            'originalPath' => 'pages/old.md',
            'deletedAt' => '2020-01-01T00:00:00Z',
            'trashFilename' => 'old.md.trash',
        ];
        vfsStream::newFile('old-item.meta.json')->at($trash)->setContent(JsonHelper::encode($oldMeta));
        vfsStream::newFile('old.md.trash')->at($trash)->setContent('# old');

        $freshMeta = [
            'id' => 'fresh-item',
            'originalPath' => 'pages/fresh.md',
            'deletedAt' => gmdate('c'),
            'trashFilename' => 'fresh.md.trash',
        ];
        vfsStream::newFile('fresh-item.meta.json')->at($trash)->setContent(JsonHelper::encode($freshMeta));
        vfsStream::newFile('fresh.md.trash')->at($trash)->setContent('# fresh');

        $validator = new FileValidator(vfsStream::url('storage'));
        $service = new TrashService(new FileReader($validator));

        $removed = $service->purgeOlderThan(7);

        $this->assertSame(1, $removed);
        $this->assertSame(['fresh-item'], array_column($service->listItems(), 'id'));
    }
}
