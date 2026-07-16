<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Services;

use PaginiumCMS\Modules\Audit\Contracts\AuditorInterface;
use PaginiumCMS\Modules\Audit\Models\AuditIssue;
use PaginiumCMS\Modules\Audit\Models\AuditSeverity;
use PaginiumCMS\Support\FileHelper;

/**
 * Auditor pre kontrolu konfigurácie.
 */
class ConfigurationAuditor implements AuditorInterface
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function getName(): string
    {
        return 'configuration';
    }

    public function getDescription(): string
    {
        return 'Kontroluje konfiguráciu systému.';
    }

    /**
     * @param array<int|string, mixed> $options
     * @return array<int|string, mixed>
     */
    public function run(array $options = []): array
    {
        $issues = [];

        // 1. Kontrola .env súboru
        $issues = array_merge($issues, $this->checkEnvConfiguration());

        // 2. Kontrola duplicitných konfigurácií
        $issues = array_merge($issues, $this->checkDuplicateConfig());

        // 3. Kontrola chýbajúcich povinných nastavení
        $issues = array_merge($issues, $this->checkRequiredSettings());

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkEnvConfiguration(): array
    {
        $issues = [];
        $envPath = $this->basePath . '/.env';

        if (!file_exists($envPath)) {
            return $issues;
        }

        $content = FileHelper::read($envPath);
        $required = ['APP_ENV', 'APP_DEBUG', 'APP_URL'];

        foreach ($required as $key) {
            if (!preg_match('/^' . $key . '=/m', $content)) {
                $issues[] = (new AuditIssue(
                    AuditSeverity::ERROR,
                    'configuration',
                    'Chýba povinná konfigurácia: ' . $key,
                    'Nastavenie ' . $key . ' nie je definované v .env súbore.'
                ))->setRecommendation('Pridajte ' . $key . ' do .env súboru.');
            }
        }

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkDuplicateConfig(): array
    {
        $issues = [];
        $configPath = $this->basePath . '/backend/config';

        if (!is_dir($configPath)) {
            return $issues;
        }

        $files = glob($configPath . '/*.php');
        if ($files === false) {
            return $issues;
        }

        $configs = [];

        foreach ($files as $file) {
            $basename = basename($file, '.php');
            if (in_array($basename, $configs)) {
                $issues[] = (new AuditIssue(
                    AuditSeverity::WARNING,
                    'configuration',
                    'Duplicitná konfigurácia: ' . $basename,
                    'Konfiguračný súbor ' . $basename . '.php sa nachádza na viacerých miestach.'
                ));
            }
            $configs[] = $basename;
        }

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkRequiredSettings(): array
    {
        $issues = [];
        $requiredSettings = [
            'site_title' => 'Názov stránky',
            'site_url' => 'URL stránky',
            'admin_email' => 'Admin email',
        ];

        foreach ($requiredSettings as $key => $label) {
            $value = getenv($key);
            if (empty($value)) {
                $issues[] = (new AuditIssue(
                    AuditSeverity::WARNING,
                    'configuration',
                    'Chýba nastavenie: ' . $key,
                    'Nastavenie ' . $label . ' nie je definované.'
                ))->setRecommendation('Pridajte ' . $key . ' do konfigurácie systému.');
            }
        }

        return $issues;
    }
}
