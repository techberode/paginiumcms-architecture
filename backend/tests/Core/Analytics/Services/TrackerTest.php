<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Services\Tracker;
use PaginiumCMS\Core\Analytics\Services\GeoIPService;
use PaginiumCMS\Core\Analytics\Models\Visit;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Support\FileHelper;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class TrackerTest extends TestCase
{
    private Tracker $tracker;
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        // Reálna štruktúra – rovnaká ako v aplikácii
        $structure = [
            'storage' => [
                'app' => [
                    'content' => [
                        'data' => [
                            'analytics' => [
                                'visits' => [],
                                'visitors' => [],
                                'daily' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $root = vfsStream::setup('project', null, $structure);
        $this->root = vfsStream::url('project');

        // FileValidator nastavený na storage/app/content (ako v reálnej aplikácii)
        $validator = new FileValidator($this->root . '/storage/app/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $geoIP = new GeoIPService();
        $this->tracker = new Tracker($reader, $writer, $geoIP, 'data/analytics');
    }

    public function testTrackVisit(): void
    {
        $visit = new Visit();
        $visit->setIp('8.8.8.8');
        $visit->setRequestUri('/test-page');
        $visit->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $this->tracker->track($visit);

        $date = date('Y-m-d');
        // Správna cesta: storage/app/content/data/analytics/visits/
        $filePath = $this->root . '/storage/app/content/data/analytics/visits/' . $date . '.json';
        $this->assertFileExists($filePath, 'Súbor nebol vytvorený na ceste: ' . $filePath);

        $data = FileHelper::readJson($filePath);
        $this->assertCount(1, $data);
        $this->assertEquals('/test-page', $data[0]['requestUri']);
    }

    public function testGetVisits(): void
    {
        $visit = new Visit();
        $visit->setIp('8.8.8.8');
        $visit->setRequestUri('/test-page');
        $this->tracker->track($visit);

        $date = date('Y-m-d');
        $visits = $this->tracker->getVisits($date);
        $this->assertNotEmpty($visits);
        $this->assertEquals('/test-page', $visits[0]['requestUri']);
    }

    public function testGetDailyStats(): void
    {
        $visit = new Visit();
        $visit->setIp('8.8.8.8');
        $visit->setRequestUri('/test-page');
        $this->tracker->track($visit);

        $date = date('Y-m-d');
        $stats = $this->tracker->getDailyStats($date);
        $this->assertArrayHasKey('date', $stats);
        $this->assertArrayHasKey('visits', $stats);
        $this->assertEquals($date, $stats['date']);
        $this->assertGreaterThan(0, $stats['visits']);
    }
}
