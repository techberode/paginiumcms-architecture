<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\Contracts\MediaStorageDriverInterface;

trait MediaStorageDriverContractTestTrait
{
    abstract protected function createDriver(): MediaStorageDriverInterface;

    public function testContractPutReadDeleteAndChecksum(): void
    {
        $driver = $this->createDriver();
        $path = 'media/contract-test.bin';
        $payload = 'binary-payload';

        $driver->put($path, $payload);
        $this->assertTrue($driver->exists($path));
        $this->assertSame($payload, $driver->read($path));
        $this->assertSame(hash('sha256', $payload), $driver->checksum($path));

        $driver->delete($path);
        $this->assertFalse($driver->exists($path));
    }

    public function testContractRejectsTraversalPath(): void
    {
        $driver = $this->createDriver();

        $this->expectException(FlatFileException::class);
        $driver->put('media/../secret.bin', 'x');
    }

    public function testContractHealthProbe(): void
    {
        $health = $this->createDriver()->health();

        $this->assertTrue($health['ok']);
        $this->assertNotSame('', $health['driver']);
        $this->assertGreaterThanOrEqual(0, $health['latencyMs']);
    }
}
