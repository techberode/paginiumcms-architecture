<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Scheduler\Handlers;

use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Logging\Services\AdminExportArtifactStore;
use PaginiumCMS\Core\Logging\Services\ApplicationLogExportService;
use PaginiumCMS\Core\Logging\Services\ApplicationLogMessageFormatter;
use PaginiumCMS\Core\Logging\Services\ApplicationLogReader;
use PaginiumCMS\Core\Scheduler\Handlers\AdminApplicationLogExportHandler;
use PHPUnit\Framework\TestCase;

final class AdminApplicationLogExportHandlerTest extends TestCase
{
    public function testRejectsInvalidPayload(): void
    {
        $handler = new AdminApplicationLogExportHandler(
            $this->exportService(),
            $this->artifactStore(),
        );

        $result = $handler->handle([]);

        $this->assertFalse($result->success);
    }

    public function testMarksFailedWhenNoEntries(): void
    {
        $store = $this->artifactStore();
        $pending = $store->createPending('user-1', 'txt');

        $handler = new AdminApplicationLogExportHandler($this->exportService(), $store);
        $result = $handler->handle([
            'artifact_id' => $pending['id'],
            'format' => 'txt',
        ]);

        $this->assertFalse($result->success);
        $entry = $store->findForUser($pending['id'], 'user-1');
        $this->assertSame('failed', $entry['status'] ?? null);
    }

    private function artifactStore(): AdminExportArtifactStore
    {
        vfsStream::setup('root', null, ['data' => []]);
        $base = vfsStream::url('root');
        $validator = new FileValidator($base);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        return new AdminExportArtifactStore($reader, $writer, $validator);
    }

    private function exportService(): ApplicationLogExportService
    {
        $base = sys_get_temp_dir() . '/pag-export-handler-' . uniqid('', true);
        mkdir($base . '/app', 0777, true);

        $reader = new ApplicationLogReader(['app' => $base . '/app']);
        $formatter = new ApplicationLogMessageFormatter();

        return new ApplicationLogExportService($reader, $formatter);
    }
}
