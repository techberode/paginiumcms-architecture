<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Blueprint\Services;

use PaginiumCMS\Core\Blueprint\Services\BlueprintRepository;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class BlueprintRepositoryTest extends TestCase
{
    public function testReturnsBuiltInPageBlueprint(): void
    {
        vfsStream::setup('root');

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));

        $repo = new BlueprintRepository($reader);
        $blueprint = $repo->get('page');

        $this->assertSame('page', $blueprint->type);
        $this->assertTrue($blueprint->system);
        $this->assertNotEmpty($blueprint->fields);
    }

    public function testPersistsCustomBlueprint(): void
    {
        vfsStream::setup('root');

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));

        $repo = new BlueprintRepository($reader);
        $source = $repo->get('article');
        $saved = $repo->save($source);

        $this->assertFileExists(vfsStream::url('root/data/blueprints/article.json'));
        $this->assertSame('article', $saved->type);
    }
}
