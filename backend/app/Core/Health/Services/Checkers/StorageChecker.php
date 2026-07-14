<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services\Checkers;

use PaginiumCMS\Core\Health\Contracts\HealthCheckInterface;
use PaginiumCMS\Core\Health\Models\HealthStatus;

class StorageChecker implements HealthCheckInterface
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');
    }

    public function getName(): string { return 'storage'; }
    public function getDescription(): string { return 'Kontrola úložiska a oprávnení'; }
    public function getGroup(): string { return 'storage'; }

    public function check(): HealthStatus
    {
        $start = microtime(true);
        $issues = [];
        $data = [];

        // 1. Adresáre
        $directories = [
            'storage' => $this->storagePath,
            'storage/app' => $this->storagePath . '/app',
            'storage/app/content' => $this->storagePath . '/app/content',
            'storage/logs' => $this->storagePath . '/logs',
            'storage/cache' => $this->storagePath . '/cache',
            'storage/backups' => $this->storagePath . '/backups',
        ];

        foreach ($directories as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $data[$name] = ['exists' => $exists, 'writable' => $writable];

            if (!$exists) {
                $issues[] = "Adresár $name neexistuje";
            } elseif (!$writable) {
                $issues[] = "Adresár $name nie je zapisovateľný";
            }
        }

        // 2. Voľné miesto
        $freeSpace = disk_free_space($this->storagePath);
        $data['free_space'] = $freeSpace !== false ? $this->formatSize((float)$freeSpace) : 'N/A';
        $data['free_space_bytes'] = $freeSpace;

        if ($freeSpace !== false && $freeSpace < 104857600) { // 100 MB
            $issues[] = 'Voľné miesto je menšie ako 100 MB';
        }

        $status = empty($issues) ? HealthStatus::STATUS_PASS : HealthStatus::STATUS_WARN;
        $message = empty($issues) ? 'Úložisko je v poriadku' : implode(', ', $issues);

        $check = new HealthStatus($this->getName(), $status, $message);
        $check->setData($data);
        $check->setDuration(microtime(true) - $start);

        return $check;
    }

    private function formatSize(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
