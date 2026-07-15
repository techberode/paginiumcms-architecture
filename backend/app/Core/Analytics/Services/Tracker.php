<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Models\Visit;
use PaginiumCMS\Core\Analytics\Models\Visitor;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;

class Tracker implements TrackerInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private GeoIPService $geoIP;
    private string $storagePath;
    private array $excludeIps = ['127.0.0.1', '::1'];
    private array $excludePaths = ['/api/', '/admin/', '/assets/', '/favicon.ico'];
    private string $basePath;

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        GeoIPService $geoIP,
        string $storagePath = 'data/analytics'
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->geoIP = $geoIP;
        $this->storagePath = rtrim($storagePath, '/');
        // Použijeme priamo basePath z reader-a (storage/app/content)
        $this->basePath = $reader->getBasePath();
    }

    public function track(Visit $visit): void
    {
        if (in_array($visit->getIp(), $this->excludeIps, true)) {
            return;
        }

        foreach ($this->excludePaths as $path) {
            if (strpos($visit->getRequestUri() ?? '', $path) === 0) {
                return;
            }
        }

        $this->saveVisit($visit);
        $this->updateVisitor($visit);
        $this->updateDailyStats($visit);
    }

    public function getVisitor(string $visitorId): ?Visitor
    {
        $path = $this->getFullPath('visitors/' . $visitorId . '.json');
        if (!file_exists($path)) {
            return null;
        }
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        $data = json_decode($content, true);
        return $data ? $this->hydrateVisitor($data) : null;
    }

    public function getVisits(?string $date = null, int $limit = 100): array
    {
        $date = $date ?? date('Y-m-d');
        $path = $this->getFullPath('visits/' . $date . '.json');
        if (!file_exists($path)) {
            return [];
        }
        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $data = json_decode($content, true);
        return $data ? array_slice($data, -$limit) : [];
    }

    public function getDailyStats(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $path = $this->getFullPath('daily/' . $date . '.json');
        if (!file_exists($path)) {
            return $this->getEmptyStats($date);
        }
        $content = file_get_contents($path);
        if ($content === false) {
            return $this->getEmptyStats($date);
        }
        return json_decode($content, true) ?? $this->getEmptyStats($date);
    }

    public function getRealtimeVisitors(): array
    {
        $cutoff = time() - 300;
        $visitors = [];
        $visitsDir = $this->getFullPath('visits/');
        if (!is_dir($visitsDir)) {
            return [];
        }
        $files = glob($visitsDir . '*.json');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            $data = json_decode($content, true);
            if ($data) {
                foreach ($data as $visit) {
                    if (strtotime($visit['timestamp']) > $cutoff) {
                        $visitors[] = $visit;
                    }
                }
            }
        }
        return $visitors;
    }

    private function saveVisit(Visit $visit): void
    {
        $date = date('Y-m-d');
        $dir = $this->getFullPath('visits/');
        $path = $dir . $date . '.json';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $visits = [];
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $visits = json_decode($content, true) ?? [];
            }
        }

        $visits[] = $visit->toArray();
        if (count($visits) > 10000) {
            $visits = array_slice($visits, -10000);
        }

        file_put_contents($path, json_encode($visits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function updateVisitor(Visit $visit): void
    {
        $visitorId = $visit->getVisitorId();
        $dir = $this->getFullPath('visitors/');
        $path = $dir . $visitorId . '.json';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $location = $this->geoIP->getLocation($visit->getIp());
        $deviceInfo = (new DeviceDetector($visit->getUserAgent()))->getAll();

        $visitorData = [
            'visitorId' => $visitorId,
            'firstVisit' => date('Y-m-d H:i:s'),
            'lastVisit' => $visit->getTimestamp(),
            'visitCount' => 1,
            'ip' => $visit->getIp(),
            'country' => $location ? $location->getCountry() : null,
            'city' => $location ? $location->getCity() : null,
            'device' => $deviceInfo['device'],
            'deviceType' => $deviceInfo['deviceType'],
            'os' => $deviceInfo['os'],
            'browser' => $deviceInfo['browser'],
        ];

        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $existing = json_decode($content, true);
                if ($existing) {
                    $visitorData['visitCount'] = ($existing['visitCount'] ?? 0) + 1;
                    $visitorData['firstVisit'] = $existing['firstVisit'] ?? $visit->getTimestamp();
                    if (empty($visitorData['country']) && !empty($existing['country'])) {
                        $visitorData['country'] = $existing['country'];
                    }
                }
            }
        }

        file_put_contents($path, json_encode($visitorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function updateDailyStats(Visit $visit): void
    {
        $date = date('Y-m-d');
        $dir = $this->getFullPath('daily/');
        $path = $dir . $date . '.json';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stats = $this->getDailyStats($date);
        $stats['date'] = $date;
        $stats['visits'] = ($stats['visits'] ?? 0) + 1;
        $stats['page_views'] = ($stats['page_views'] ?? 0) + 1;
        $stats['unique_visitors'] = ($stats['unique_visitors'] ?? 0) + 1;

        file_put_contents($path, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function getFullPath(string $path): string
    {
        return $this->basePath . '/' . $this->storagePath . '/' . $path;
    }

    private function getEmptyStats(string $date): array
    {
        return [
            'date' => $date,
            'visits' => 0,
            'unique_visitors' => 0,
            'page_views' => 0,
            'bounce_rate' => 0,
        ];
    }

    private function hydrateVisitor(array $data): Visitor
    {
        return new Visitor($data['visitorId']);
    }
}
