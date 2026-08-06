<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Media\Services\LocalMediaStorageDriver;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class LocalMediaStorageDriverTest extends TestCase
{
    private LocalMediaStorageDriver $driver;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $this->driver = new LocalMediaStorageDriver(new FileReader($validator), new FileWriter($validator));
    }

    public function testPutReadDeleteAndChecksum(): void
    {
        $path = 'media/test.bin';
        $payload = 'binary-payload';

        $this->driver->put($path, $payload);
        $this->assertTrue($this->driver->exists($path));
        $this->assertSame($payload, $this->driver->read($path));
        $this->assertSame(hash('sha256', $payload), $this->driver->checksum($path));

        $this->driver->delete($path);
        $this->assertFalse($this->driver->exists($path));
    }

    public function testPublicUrlUsesStoragePrefix(): void
    {
        $this->assertSame(
            '/storage/app/content/media/photo.png',
            $this->driver->publicUrl('media/photo.png')
        );
    }

    public function testHealthProbeSucceeds(): void
    {
        $health = $this->driver->health();

        $this->assertTrue($health['ok']);
        $this->assertSame('local', $health['driver']);
        $this->assertGreaterThanOrEqual(0, $health['latencyMs']);
    }
}
