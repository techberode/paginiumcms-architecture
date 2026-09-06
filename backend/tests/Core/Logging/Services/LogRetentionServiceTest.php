<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Logging\LogStoragePaths;
use PaginiumCMS\Core\Logging\Services\LogRetentionService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class LogRetentionServiceTest extends TestCase
{
    /** @var list<string> */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    public function testPurgesOldFilesAcrossAllSources(): void
    {
        $oldDate = date('Y-m-d', strtotime('-45 days'));

        foreach (LogStoragePaths::readerSources() as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            $file = $path . '/' . $oldDate . '.json';
            file_put_contents($file, '[]');
            $this->createdFiles[] = $file;
        }

        $validator = new FileValidator(LogStoragePaths::base());
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('logging')->willReturn(['retentionDays' => 30]);

        $service = new LogRetentionService($reader, $writer, $settings);
        $result = $service->purgeOldLogs();

        $this->assertSame(1, $result['app']);
        $this->assertSame(1, $result['audit']);
        $this->assertSame(1, $result['event']);
        $this->assertSame(1, $result['user']);
        $this->assertSame(30, $result['retention_days']);

        foreach ($this->createdFiles as $file) {
            $this->assertFileDoesNotExist($file);
        }
        $this->createdFiles = [];
    }
}
