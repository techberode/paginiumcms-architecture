<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Services;

use PaginiumCMS\Modules\Audit\Contracts\AuditorInterface;
use PaginiumCMS\Modules\Audit\Models\AuditIssue;
use PaginiumCMS\Modules\Audit\Models\AuditSeverity;

/**
 * Auditor pre bezpečnostné kontroly.
 */
class SecurityAuditor implements AuditorInterface
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function getName(): string
    {
        return 'security';
    }

    public function getDescription(): string
    {
        return 'Kontroluje bezpečnostné nastavenia a konfiguráciu.';
    }

    public function run(array $options = []): array
    {
        $issues = [];

        // 1. Kontrola .env súboru
        $issues = array_merge($issues, $this->checkEnvFile());

        // 2. Kontrola debug módu
        $issues = array_merge($issues, $this->checkDebugMode());

        // 3. Kontrola oprávnení adresárov
        $issues = array_merge($issues, $this->checkDirectoryPermissions());

        // 4. Kontrola .htaccess súborov
        $issues = array_merge($issues, $this->checkHtaccessFiles());

        // 5. Kontrola session nastavení
        $issues = array_merge($issues, $this->checkSessionSettings());

        return $issues;
    }

    private function checkEnvFile(): array
    {
        $issues = [];
        $envPath = $this->basePath . '/.env';

        if (!file_exists($envPath)) {
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'security',
                'Chýba .env súbor',
                'Súbor .env nebol nájdený. Tento súbor je potrebný pre konfiguráciu prostredia.'
            ))->setRecommendation('Vytvorte .env súbor na základe .env.example');
        }

        return $issues;
    }

    private function checkDebugMode(): array
    {
        $issues = [];
        $debug = getenv('APP_DEBUG') ?? 'false';

        if ($debug === 'true' || $debug === '1') {
            $issues[] = (new AuditIssue(
                AuditSeverity::CRITICAL,
                'security',
                'Debug mód je zapnutý v produkcii',
                'APP_DEBUG je nastavený na true. Toto môže odhaliť citlivé informácie.'
            ))->setRecommendation('Nastavte APP_DEBUG=false v .env súbore pre produkčné prostredie.');
        }

        return $issues;
    }

    private function checkDirectoryPermissions(): array
    {
        $issues = [];
        $directories = [
            'storage' => 0755,
            'storage/app' => 0755,
            'storage/logs' => 0755,
            'storage/cache' => 0755,
        ];

        foreach ($directories as $dir => $expectedPerm) {
            $fullPath = $this->basePath . '/' . $dir;
            if (is_dir($fullPath)) {
                $perm = fileperms($fullPath) & 0777;
                if ($perm !== $expectedPerm) {
                    $issues[] = (new AuditIssue(
                        AuditSeverity::WARNING,
                        'security',
                        'Nesprávne oprávnenia adresára: ' . $dir,
                        sprintf('Očakávané: %o, Aktuálne: %o', $expectedPerm, $perm)
                    ))->setRecommendation(sprintf('Spustite: chmod %o %s', $expectedPerm, $fullPath));
                }
            }
        }

        return $issues;
    }

    private function checkHtaccessFiles(): array
    {
        $issues = [];
        $storagePath = $this->basePath . '/storage';
        $htaccessPath = $storagePath . '/.htaccess';

        if (!file_exists($htaccessPath)) {
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'security',
                'Chýba .htaccess v storage adresári',
                'Súbor .htaccess chráni storage adresár pred priamym prístupom.'
            ))->setRecommendation('Vytvorte .htaccess súbor v storage adresári s obsahom "Deny from all"');
        }

        return $issues;
    }

    private function checkSessionSettings(): array
    {
        $issues = [];

        // Kontrola session nastavení v PHP
        if (ini_get('session.cookie_httponly') != 1) {
            $issues[] = (new AuditIssue(
                AuditSeverity::ERROR,
                'security',
                'Session cookie nie je označená ako HttpOnly',
                'HttpOnly zabraňuje prístupu k session cookie cez JavaScript.'
            ))->setRecommendation('Nastavte session.cookie_httponly=1 v php.ini');
        }

        if (ini_get('session.cookie_secure') != 1 && isset($_SERVER['HTTPS'])) {
            $issues[] = (new AuditIssue(
                AuditSeverity::ERROR,
                'security',
                'Session cookie nie je označená ako Secure',
                'Secure zabezpečuje, že cookie sa odosiela iba cez HTTPS.'
            ))->setRecommendation('Nastavte session.cookie_secure=1 v php.ini');
        }

        if (ini_get('session.use_strict_mode') != 1) {
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'security',
                'Strict mode pre session nie je zapnutý',
                'Strict mode zabraňuje použitiu neinicializovaných session ID.'
            ))->setRecommendation('Nastavte session.use_strict_mode=1 v php.ini');
        }

        return $issues;
    }
}
