<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Media\Services\LocalMediaStorageDriver;
use PaginiumCMS\Modules\Media\Services\MediaUrlResolver;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class LocalMediaStorageDriverTest extends TestCase
{
    use MediaStorageDriverContractTestTrait;

    private LocalMediaStorageDriver $driver;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $this->driver = new LocalMediaStorageDriver(
            new FileReader($validator),
            new FileWriter($validator),
            new MediaUrlResolver(),
        );
    }

    protected function createDriver(): LocalMediaStorageDriver
    {
        return $this->driver;
    }

    public function testPublicUrlUsesStoragePrefix(): void
    {
        $this->assertSame(
            '/storage/app/content/media/photo.png',
            $this->driver->publicUrl('media/photo.png')
        );
    }

    public function testHealthProbeDriverName(): void
    {
        $this->assertSame('local', $this->driver->health()['driver']);
    }
}
