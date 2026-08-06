<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Media\Exception\UnknownMediaStorageDriverException;
use PaginiumCMS\Modules\Media\Services\LocalMediaStorageDriver;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class MediaStorageFactoryTest extends TestCase
{
    private MediaStorageFactory $factory;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $this->factory = new MediaStorageFactory(new FileReader($validator), new FileWriter($validator));
    }

    public function testDefaultCreatesLocalDriver(): void
    {
        $driver = $this->factory->create(null);

        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
        $this->assertTrue($driver->health()['ok']);
    }

    public function testLocalDriverExplicit(): void
    {
        $driver = $this->factory->create('local');

        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
    }

    public function testS3FallsBackToLocal(): void
    {
        $this->assertSame('local', MediaStorageFactory::normalizeDriver('s3'));
        $this->assertSame(
            'local',
            MediaStorageFactory::driverFromMediaSettings(['storageDriver' => 's3'])
        );

        $driver = $this->factory->create('s3');
        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
    }

    public function testUnknownDriverFallsBackToLocalWithAllowFallback(): void
    {
        $driver = $this->factory->create('ftp');

        $this->assertInstanceOf(LocalMediaStorageDriver::class, $driver);
    }

    public function testUnknownDriverWithoutFallbackThrows(): void
    {
        $this->expectException(UnknownMediaStorageDriverException::class);
        $this->factory->create('ftp', false);
    }
}
