<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Analytics\Services;

use PaginiumCMS\Core\Analytics\Contracts\TrackerInterface;
use PaginiumCMS\Core\Analytics\Models\Visit;
use PaginiumCMS\Core\Analytics\Models\Visitor;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\FileHelper;
use PaginiumCMS\Support\JsonHelper;

class Tracker implements TrackerInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private GeoIPService $geoIP;
    private string $storagePath;
    /** @var list<string> */
    private array $excludeIps = ['127.0.0.1', '::1'];
    /** @var list<string> */
    private array $excludePaths = ['/api/', '/admin/', '/assets/', '/favicon.ico'];

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
        $relativePath = $this->storageRelativePath('visitors/' . $visitorId . '.json');
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        $data = JsonHelper::decode($this->reader->read($relativePath));

        return $data !== [] ? $this->hydrateVisitor($data) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getVisits(?string $date = null, int $limit = 100): array
    {
        $date = $date ?? date('Y-m-d');
        $relativePath = $this->storageRelativePath('visits/' . $date . '.json');
        if (!$this->reader->exists($relativePath)) {
            return [];
        }

        $data = JsonHelper::decode($this->reader->read($relativePath));

        return $data !== [] ? array_values(array_slice($data, -$limit)) : [];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getDailyStats(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $relativePath = $this->storageRelativePath('daily/' . $date . '.json');
        if (!$this->reader->exists($relativePath)) {
            return $this->getEmptyStats($date);
        }

        return JsonHelper::decode($this->reader->read($relativePath));
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getRealtimeVisitors(): array
    {
        $cutoff = time() - 300;
        $visitors = [];
        $visitsDir = $this->getFullPath('visits/');
        if (!is_dir($visitsDir)) {
            return [];
        }

        $files = glob($visitsDir . '*.json') ?: [];
        foreach ($files as $file) {
            $data = JsonHelper::decode(FileHelper::read($file));
            foreach ($data as $visit) {
                if (!is_array($visit)) {
                    continue;
                }
                if (strtotime((string) ($visit['timestamp'] ?? '')) > $cutoff) {
                    $visitors[] = $visit;
                }
            }
        }

        return $visitors;
    }

    private function saveVisit(Visit $visit): void
    {
        $date = date('Y-m-d');
        $relativePath = $this->storageRelativePath('visits/' . $date . '.json');

        $visits = [];
        if ($this->reader->exists($relativePath)) {
            $visits = JsonHelper::decode($this->reader->read($relativePath));
        }

        $visits[] = $visit->toArray();
        if (count($visits) > 10000) {
            $visits = array_slice($visits, -10000);
        }

        $this->writer->write($relativePath, JsonHelper::encode($visits), false);
    }

    private function updateVisitor(Visit $visit): void
    {
        $visitorId = $visit->getVisitorId();
        $relativePath = $this->storageRelativePath('visitors/' . $visitorId . '.json');

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

        if ($this->reader->exists($relativePath)) {
            $existing = JsonHelper::decode($this->reader->read($relativePath));
            if ($existing !== []) {
                $visitorData['visitCount'] = ($existing['visitCount'] ?? 0) + 1;
                $visitorData['firstVisit'] = $existing['firstVisit'] ?? $visit->getTimestamp();
                if (empty($visitorData['country']) && !empty($existing['country'])) {
                    $visitorData['country'] = $existing['country'];
                }
            }
        }

        $this->writer->write($relativePath, JsonHelper::encode($visitorData), false);
    }

    private function updateDailyStats(Visit $visit): void
    {
        $date = date('Y-m-d');
        $relativePath = $this->storageRelativePath('daily/' . $date . '.json');

        $stats = $this->getDailyStats($date);
        $stats['date'] = $date;
        $stats['visits'] = ($stats['visits'] ?? 0) + 1;
        $stats['page_views'] = ($stats['page_views'] ?? 0) + 1;
        $stats['unique_visitors'] = ($stats['unique_visitors'] ?? 0) + 1;

        $this->writer->write($relativePath, JsonHelper::encode($stats), false);
    }

    private function storageRelativePath(string $path): string
    {
        return $this->storagePath . '/' . ltrim($path, '/');
    }

    private function getFullPath(string $path): string
    {
        return $this->reader->getBasePath() . '/' . $this->storagePath . '/' . ltrim($path, '/');
    }

    /**
     * @return array<int|string, mixed>
     */
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

    /**
     * @param array<int|string, mixed> $data
     */
    private function hydrateVisitor(array $data): Visitor
    {
        return new Visitor((string) ($data['visitorId'] ?? ''));
    }
}
