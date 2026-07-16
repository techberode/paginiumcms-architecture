<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Services;

use PaginiumCMS\Modules\Audit\Contracts\AuditorInterface;
use PaginiumCMS\Modules\Audit\Models\AuditIssue;
use PaginiumCMS\Modules\Audit\Models\AuditSeverity;
use PaginiumCMS\Support\FileHelper;

/**
 * Auditor pre kontrolu kompatibility.
 */
class CompatibilityAuditor implements AuditorInterface
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function getName(): string
    {
        return 'compatibility';
    }

    public function getDescription(): string
    {
        return 'Kontroluje kompatibilitu verzií PHP, Slim a závislostí.';
    }

    /**
     * @param array<int|string, mixed> $options
     * @return array<int|string, mixed>
     */
    public function run(array $options = []): array
    {
        $issues = [];

        // 1. Kontrola PHP verzie
        $issues = array_merge($issues, $this->checkPhpVersion());

        // 2. Kontrola Composer závislostí
        $issues = array_merge($issues, $this->checkComposerDependencies());

        // 3. Kontrola Slim verzie
        $issues = array_merge($issues, $this->checkSlimVersion());

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkPhpVersion(): array
    {
        $issues = [];
        $requiredVersion = '8.4.0';
        $currentVersion = PHP_VERSION;

        if (version_compare($currentVersion, $requiredVersion, '<')) {
            $issues[] = (new AuditIssue(
                AuditSeverity::ERROR,
                'compatibility',
                'Nekompatibilná PHP verzia',
                sprintf('Požadovaná verzia: %s, aktuálna: %s', $requiredVersion, $currentVersion)
            ))->setRecommendation('Aktualizujte PHP na verziu ' . $requiredVersion . ' alebo vyššiu.');
        } else {
            $issues[] = (new AuditIssue(
                AuditSeverity::INFO,
                'compatibility',
                'PHP verzia je v poriadku',
                'PHP verzia ' . $currentVersion . ' spĺňa požiadavky.'
            ));
        }

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkComposerDependencies(): array
    {
        $issues = [];
        $lockPath = $this->basePath . '/composer.lock';

        if (!file_exists($lockPath)) {
            $issues[] = (new AuditIssue(
                AuditSeverity::WARNING,
                'compatibility',
                'Chýba composer.lock',
                'Súbor composer.lock nebol nájdený. Závislosti nie sú uzamknuté.'
            ))->setRecommendation('Spustite composer install pre vygenerovanie composer.lock');
            return $issues;
        }

        $lock = FileHelper::readJson($lockPath);
        $packages = $lock['packages'] ?? [];

        // Kontrola známych zastaraných balíkov
        $deprecated = [
            'symfony/yaml' => '7.0.0',
            'league/commonmark' => '2.0.0',
        ];

        foreach ($packages as $package) {
            $name = $package['name'] ?? '';
            $version = $package['version'] ?? '';

            if (isset($deprecated[$name])) {
                $required = $deprecated[$name];
                if (version_compare(ltrim($version, 'v'), $required, '<')) {
                    $issues[] = (new AuditIssue(
                        AuditSeverity::WARNING,
                        'compatibility',
                        'Zastaraný balík: ' . $name,
                        sprintf('Aktuálna verzia: %s, odporúčaná: %s', $version, $required)
                    ))->setRecommendation('Aktualizujte balík ' . $name . ' na verziu ' . $required);
                }
            }
        }

        return $issues;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function checkSlimVersion(): array
    {
        $issues = [];

        if (class_exists('\Slim\App')) {
            $issues[] = (new AuditIssue(
                AuditSeverity::INFO,
                'compatibility',
                'Slim Framework je nainštalovaný',
                'Slim Framework 4 je správne nainštalovaný.'
            ));
        } else {
            $issues[] = (new AuditIssue(
                AuditSeverity::ERROR,
                'compatibility',
                'Slim Framework nie je nainštalovaný',
                'Slim Framework 4 je potrebný pre chod aplikácie.'
            ))->setRecommendation('Nainštalujte Slim Framework: composer require slim/slim ^4.0');
        }

        return $issues;
    }
}
