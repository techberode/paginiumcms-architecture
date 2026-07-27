<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Services;

use PaginiumCMS\Core\Scheduler\Services\JobRegistryStore;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PHPUnit\Framework\TestCase;

final class JobRegistryStoreTest extends TestCase
{
    /** @var array<string, string> */
    private array $files = [];

    public function testSystemJobPayloadCannotBeOverwritten(): void
    {
        $store = $this->makeStore();

        $store->save([
            'id' => 'system-deploy',
            'name' => 'System code deploy',
            'handler' => 'system.deploy',
            'cron' => '0 0 1 1 *',
            'enabled' => true,
            'system' => true,
            'payload' => ['ref' => 'v9.9.9-evil'],
        ]);

        $job = $store->find('system-deploy');
        $this->assertNotNull($job);
        $this->assertSame([], $job['payload']);
    }

    public function testNonSystemJobPayloadIsPreserved(): void
    {
        $store = $this->makeStore();

        $store->save([
            'id' => 'custom-job',
            'name' => 'Custom',
            'handler' => 'backup.scheduled',
            'cron' => '0 3 * * *',
            'enabled' => true,
            'system' => false,
            'payload' => ['force' => true],
        ]);

        $job = $store->find('custom-job');
        $this->assertNotNull($job);
        $this->assertSame(['force' => true], $job['payload']);
    }

    private function makeStore(): JobRegistryStore
    {
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturnCallback(fn (string $path): bool => isset($this->files[$path]));
        $reader->method('read')->willReturnCallback(fn (string $path): string => $this->files[$path] ?? '');

        $writer = $this->createMock(FileWriterInterface::class);
        $writer->method('write')->willReturnCallback(function (string $path, string $content): void {
            $this->files[$path] = $content;
        });

        return new JobRegistryStore($reader, $writer);
    }
}
