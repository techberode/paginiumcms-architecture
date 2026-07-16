<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Services;

use PaginiumCMS\Modules\Audit\Contracts\AuditorInterface;
use PaginiumCMS\Modules\Audit\Models\AuditIssue;
use PaginiumCMS\Modules\Audit\Models\AuditSeverity;
use PaginiumCMS\Support\FileHelper;

/**
 * Auditor pre kontrolu integrity súborov.
 */
class IntegrityAuditor implements AuditorInterface
{
    private string $basePath;
    private string $hashFile;

    public function __construct(string $basePath, string $hashFile = 'storage/checksums.json')
    {
        $this->basePath = rtrim($basePath, '/');
        $this->hashFile = $hashFile;
    }

    public function getName(): string
    {
        return 'integrity';
    }

    public function getDescription(): string
    {
        return 'Kontroluje integritu jadrových súborov pomocou kontrolných súčtov.';
    }

    /**
     * @param array<int|string, mixed> $options
     * @return array<int|string, mixed>
     */
    public function run(array $options = []): array
    {
        $issues = [];

        // 1. Kontrola existencie hash súboru
        $hashPath = $this->basePath . '/' . $this->hashFile;
        if (!file_exists($hashPath)) {
            $issues[] = (new AuditIssue(
                AuditSeverity::ERROR,
                'integrity',
                'Chýba súbor s kontrolnými súčtami',
                'Súbor ' . $this->hashFile . ' nebol nájdený. Nie je možné overiť integritu.'
            ))->setRecommendation('Spustite príkaz na vygenerovanie kontrolných súčtov: php bin/audit generate-checksums');
            return $issues;
        }

        // 2. Načítanie hashov
        try {
            $hashes = FileHelper::readJson($hashPath);
        } catch (\JsonException) {
            $issues[] = (new AuditIssue(
                AuditSeverity::ERROR,
                'integrity',
                'Neplatný súbor s kontrolnými súčtami',
                'Súbor ' . $this->hashFile . ' obsahuje neplatný JSON.'
            ));
            return $issues;
        }

        // 3. Kontrola každého súboru
        $coreFiles = $hashes['files'] ?? [];
        $modifiedFiles = [];

        foreach ($coreFiles as $file => $expectedHash) {
            $fullPath = $this->basePath . '/' . $file;
            if (!file_exists($fullPath)) {
                $issues[] = (new AuditIssue(
                    AuditSeverity::ERROR,
                    'integrity',
                    'Chýba jadrový súbor: ' . $file,
                    'Súbor bol odstránený alebo presunutý.'
                ))->setFile($file);
                continue;
            }

            $currentHash = hash_file('sha256', $fullPath);
            if ($currentHash !== $expectedHash) {
                $modifiedFiles[] = $file;
                $issues[] = (new AuditIssue(
                    AuditSeverity::CRITICAL,
                    'integrity',
                    'Modifikovaný jadrový súbor: ' . $file,
                    'Kontrolný súčet sa nezodpovedá. Súbor bol zmenený.'
                ))->setFile($file)->setRecommendation('Skontrolujte súbor a prípadne ho obnovte z pôvodnej verzie.');
            }
        }

        // 4. Kontrola nových súborov
        if (!empty($modifiedFiles)) {
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'integrity',
                count($modifiedFiles) . ' súborov bolo modifikovaných',
                'Tieto súbory sa líšia od pôvodnej verzie: ' . implode(', ', $modifiedFiles)
            ));
        }

        // 5. Celkové zhrnutie
        if (empty($issues)) {
            $issues[] = (new AuditIssue(
                AuditSeverity::INFO,
                'integrity',
                'Všetky jadrové súbory sú v poriadku',
                'Kontrolné súčty všetkých jadrových súborov sú platné.'
            ));
        }

        return $issues;
    }
}
