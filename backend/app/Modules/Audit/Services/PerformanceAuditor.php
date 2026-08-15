<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentStalenessService;
use PaginiumCMS\Modules\Audit\Contracts\AuditorInterface;
use PaginiumCMS\Modules\Audit\Models\AuditIssue;
use PaginiumCMS\Modules\Audit\Models\AuditSeverity;

/**
 * Auditor pre kontrolu výkonu.
 */
class PerformanceAuditor implements AuditorInterface
{
    private string $basePath;
    private int $maxCacheSize;
    private int $maxLogSize;

    public function __construct(
        string $basePath,
        private ContentRepositoryInterface $content,
        private ContentStalenessService $staleness,
        int $maxCacheSize = 104857600, // 100 MB
        int $maxLogSize = 52428800 // 50 MB
    ) {
        $this->basePath = rtrim($basePath, '/');
        $this->maxCacheSize = $maxCacheSize;
        $this->maxLogSize = $maxLogSize;
    }

    public function getName(): string
    {
        return 'performance';
    }

    public function getDescription(): string
    {
        return 'Kontroluje výkon a veľkosť cache a logov.';
    }

    /**
     * @param array<int|string, mixed> $options
     * @return array<int|string, mixed>
     */
    public function run(array $options = []): array
    {
        $issues = [];

        // 1. Kontrola veľkosti cache
        $issues = array_merge($issues, $this->checkCacheSize());

        // 2. Kontrola veľkosti logov
        $issues = array_merge($issues, $this->checkLogSize());

        // 3. Kontrola OPcache
        $issues = array_merge($issues, $this->checkOpcache());

        // 4. Zastaralý publikovaný obsah (It.81e)
        $issues = array_merge($issues, $this->checkStaleContent());

        return $issues;
    }

    /**
     * @return list<AuditIssue>
     */
    private function checkStaleContent(): array
    {
        if ($this->staleness->thresholdMonths() === 0) {
            return [];
        }

        $staleCount = $this->content->countIndexed('page', ['stale' => '1'])
            + $this->content->countIndexed('article', ['stale' => '1']);

        if ($staleCount === 0) {
            return [
                new AuditIssue(
                    AuditSeverity::INFO,
                    'performance',
                    'Publikovaný obsah je aktuálny',
                    'Žiadne stránky ani články neprekročili prah zastarávajúceho obsahu.'
                ),
            ];
        }

        return [
            (new AuditIssue(
                AuditSeverity::WARNING,
                'performance',
                'Zastaralý publikovaný obsah',
                sprintf('%d položiek prekročilo prah review (%d mes.).', $staleCount, $this->staleness->thresholdMonths())
            ))->setRecommendation('Skontrolujte zoznam s filtrom stale=1 alebo redakčný kalendár.'),
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkCacheSize(): array
    {
        $issues = [];
        $cachePath = $this->basePath . '/storage/cache';

        if (!is_dir($cachePath)) {
            return $issues;
        }

        $size = $this->getDirectorySize($cachePath);

        if ($size > $this->maxCacheSize) {
            $sizeMB = round($size / 1048576, 2);
            $maxMB = round($this->maxCacheSize / 1048576, 2);
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'performance',
                'Cache adresár je príliš veľký',
                sprintf('Veľkosť: %s MB, limit: %s MB', $sizeMB, $maxMB)
            ))->setRecommendation('Vyčistite cache adresár: rm -rf storage/cache/*');
        } else {
            $sizeMB = round($size / 1048576, 2);
            $issues[] = (new AuditIssue(
                AuditSeverity::INFO,
                'performance',
                'Veľkosť cache je v poriadku',
                'Veľkosť cache: ' . $sizeMB . ' MB'
            ));
        }

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkLogSize(): array
    {
        $issues = [];
        $logPath = $this->basePath . '/storage/logs';

        if (!is_dir($logPath)) {
            return $issues;
        }

        $size = $this->getDirectorySize($logPath);

        if ($size > $this->maxLogSize) {
            $sizeMB = round($size / 1048576, 2);
            $maxMB = round($this->maxLogSize / 1048576, 2);
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'performance',
                'Log adresár je príliš veľký',
                sprintf('Veľkosť: %s MB, limit: %s MB', $sizeMB, $maxMB)
            ))->setRecommendation('Vyčistite log adresár: rm -rf storage/logs/*.log');
        } else {
            $sizeMB = round($size / 1048576, 2);
            $issues[] = (new AuditIssue(
                AuditSeverity::INFO,
                'performance',
                'Veľkosť logov je v poriadku',
                'Veľkosť logov: ' . $sizeMB . ' MB'
            ));
        }

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkOpcache(): array
    {
        $issues = [];

        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);
            if ($status !== false) {
                $memoryUsed = $status['memory_usage']['used_memory'] ?? 0;
                $memoryFree = $status['memory_usage']['free_memory'] ?? 0;
                $totalMemory = $memoryUsed + $memoryFree;

                $usagePercent = $totalMemory > 0 ? round(($memoryUsed / $totalMemory) * 100, 2) : 0;

                if ($usagePercent > 90) {
                    $issues[] = (new AuditIssue(
                        AuditSeverity::WARNING,
                        'performance',
                        'OPcache je takmer plný',
                        sprintf('Využitie OPcache: %s%%', $usagePercent)
                    ))->setRecommendation('Zvýšte opcache.memory_consumption v php.ini');
                } else {
                    $issues[] = (new AuditIssue(
                        AuditSeverity::INFO,
                        'performance',
                        'OPcache je v poriadku',
                        sprintf('Využitie OPcache: %s%%', $usagePercent)
                    ));
                }
            }
        } else {
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'performance',
                'OPcache nie je aktivovaný',
                'OPcache výrazne zlepšuje výkon PHP aplikácií.'
            ))->setRecommendation('Aktivujte OPcache v php.ini: opcache.enable=1');
        }

        return $issues;
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $files = scandir($path);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $path . '/' . $file;
            if (is_file($fullPath)) {
                $size += filesize($fullPath);
            } elseif (is_dir($fullPath)) {
                $size += $this->getDirectorySize($fullPath);
            }
        }

        return $size;
    }
}
