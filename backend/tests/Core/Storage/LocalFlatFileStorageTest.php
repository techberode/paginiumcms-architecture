<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Storage;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Storage\Drivers\LocalFlatFileStorage;
use PaginiumCMS\Core\Storage\Exception\StorageException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class LocalFlatFileStorageTest extends TestCase
{
    private LocalFlatFileStorage $storage;
    private string $root;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['data' => []]);
        $this->root = vfsStream::url('storage');
        $validator = new FileValidator($this->root);
        $this->storage = new LocalFlatFileStorage(new FileReader($validator), new FileWriter($validator), $validator);
    }

    public function testWriteAndReadParity(): void
    {
        $payload = '{"general":{"siteName":"Test"}}';
        $this->storage->write('data/settings.json', $payload);

        $this->assertTrue($this->storage->exists('data/settings.json'));
        $this->assertSame($payload, $this->storage->read('data/settings.json'));
    }

    public function testAtomicWritePreservesPreviousVersionOnFailure(): void
    {
        $base = sys_get_temp_dir() . '/pag_storage_atomic_' . uniqid('', true);
        mkdir($base . '/data', 0777, true);

        $validator = new FileValidator($base);
        $storage = new LocalFlatFileStorage(new FileReader($validator), new FileWriter($validator), $validator);

        $path = 'data/atomic.json';
        $storage->write($path, '{"version":1}');

        $absolute = $storage->resolveAbsolutePath($path);
        chmod(dirname($absolute), 0555);

        try {
            $storage->write($path, '{"version":2}');
        } catch (StorageException) {
            chmod(dirname($absolute), 0755);
            $this->assertSame('{"version":1}', file_get_contents($absolute));

            return;
        }

        chmod(dirname($absolute), 0755);
        $this->fail('Expected storage write failure on read-only directory.');
    }

    public function testRejectsPathTraversal(): void
    {
        $this->expectException(StorageException::class);
        $this->storage->read('../outside.txt');
    }

    public function testRejectsSymlinkEscape(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Symlink escape test is Unix-specific.');
        }

        $base = sys_get_temp_dir() . '/pag_storage_symlink_' . uniqid('', true);
        $outside = sys_get_temp_dir() . '/pag_storage_outside_' . uniqid('', true);
        mkdir($base . '/data', 0777, true);
        mkdir($outside, 0777, true);
        file_put_contents($outside . '/secret.txt', 'secret');
        symlink($outside, $base . '/data/link');

        $validator = new FileValidator($base);
        $storage = new LocalFlatFileStorage(new FileReader($validator), new FileWriter($validator), $validator);

        $this->expectException(StorageException::class);
        $storage->read('data/link/secret.txt');
    }

    public function testListFilesInDirectory(): void
    {
        $this->storage->write('data/a.json', '{}');
        $this->storage->write('data/b.json', '{}');

        $files = $this->storage->list('data', '*.json');
        sort($files);

        $this->assertSame(['data/a.json', 'data/b.json'], $files);
    }

    public function testDeleteRemovesFile(): void
    {
        $this->storage->write('data/remove-me.json', '{}');
        $this->storage->delete('data/remove-me.json', false);

        $this->assertFalse($this->storage->exists('data/remove-me.json'));
    }

    public function testInvalidPathThrowsStorageException(): void
    {
        $this->expectException(StorageException::class);

        $this->storage->read('../../etc/passwd');
    }

    public function testExistsAllowsPathWhenIntermediateDirectoryMissing(): void
    {
        $base = sys_get_temp_dir() . '/pag_storage_missing_data_' . uniqid('', true);
        mkdir($base, 0777, true);

        $validator = new FileValidator($base);
        $storage = new LocalFlatFileStorage(new FileReader($validator), new FileWriter($validator), $validator);

        $this->assertFalse($storage->exists('data/settings.testing.json'));
    }

    public function testRejectsWhenStorageRootIsMissing(): void
    {
        $missingBase = sys_get_temp_dir() . '/pag_storage_missing_' . uniqid('', true);
        $validator = new FileValidator($missingBase);
        $storage = new LocalFlatFileStorage(
            new FileReader($validator),
            new FileWriter($validator),
            $validator
        );

        $this->expectException(StorageException::class);
        $storage->read('data/settings.json');
    }
}
