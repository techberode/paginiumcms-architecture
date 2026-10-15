<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaRegistryReconcileService;
use PHPUnit\Framework\TestCase;

final class MediaRegistryReconcileServiceTest extends TestCase
{
    public function testReportFlagsRegistryWithoutBinary(): void
    {
        $present = (new MediaFile())
            ->setPath('media/ok.jpg')
            ->setFileName('ok.jpg');
        $ghost = (new MediaFile())
            ->setPath('media/ghost.jpg')
            ->setFileName('ghost.jpg');

        $repository = $this->createMock(MediaRepositoryInterface::class);
        $repository->method('findAll')->willReturn([$present, $ghost]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('exists')->willReturnCallback(
            static fn (string $path): bool => $path === 'media/ok.jpg'
        );
        $reader->method('getBasePath')->willReturn(sys_get_temp_dir() . '/paginium-reconcile-empty');

        $service = new MediaRegistryReconcileService($repository, $reader);
        $report = $service->report();

        $this->assertFalse($report['healthy']);
        $this->assertSame(2, $report['registryEntries']);
        $this->assertSame(['media/ghost.jpg'], $report['registryMissingFile']);
    }
}
