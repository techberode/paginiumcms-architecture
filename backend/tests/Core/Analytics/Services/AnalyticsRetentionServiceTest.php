<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use org\bovigo\vfs\vfsStream;
use PaginiumCMS\Core\Analytics\Services\AnalyticsRetentionService;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class AnalyticsRetentionServiceTest extends TestCase
{
    public function testPurgesDatedFilesOlderThanRetention(): void
    {
        $oldDate = '2020-01-01';
        $recentDate = date('Y-m-d');

        $root = vfsStream::setup('storage', null, [
            'data' => [
                'analytics' => [
                    'visits' => [
                        $oldDate . '.json' => '{}',
                        $recentDate . '.json' => '{}',
                    ],
                    'daily' => [
                        $oldDate . '.json' => '{}',
                    ],
                    'visitors' => [
                        'stale.json' => '{"lastVisit":"2020-01-01T00:00:00+00:00"}',
                        'fresh.json' => '{"lastVisit":"' . date('c') . '"}',
                    ],
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('storage'));

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('analytics')->willReturn(['retentionDays' => 90]);

        $service = new AnalyticsRetentionService($reader, $settings);
        $result = $service->purgeOldData();

        $this->assertSame(1, $result['visits']);
        $this->assertSame(1, $result['daily']);
        $this->assertSame(1, $result['visitors']);
        $this->assertSame(90, $result['retention_days']);
        $this->assertFileDoesNotExist(vfsStream::url('storage/data/analytics/visits/' . $oldDate . '.json'));
        $this->assertFileExists(vfsStream::url('storage/data/analytics/visits/' . $recentDate . '.json'));
    }
}
