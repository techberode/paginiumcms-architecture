<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services\Checkers;

use PaginiumCMS\Core\Health\Contracts\HealthCheckInterface;
use PaginiumCMS\Core\Health\Models\HealthStatus;
use PaginiumCMS\Support\AppTimezone;

class SystemChecker implements HealthCheckInterface
{
    public function getName(): string { return 'system'; }
    public function getDescription(): string { return 'Kontrola systémových požiadaviek'; }
    public function getGroup(): string { return 'system'; }

    public function check(): HealthStatus
    {
        $start = microtime(true);
        $issues = [];

        // 1. PHP verzia
        $phpVersion = PHP_VERSION;
        $requiredVersion = '8.4.0';
        if (version_compare($phpVersion, $requiredVersion, '<')) {
            $issues[] = "PHP verzia $phpVersion je nižšia ako požadovaná $requiredVersion";
        }

        // 2. Rozšírenia
        $requiredExtensions = ['json', 'mbstring', 'zip', 'curl', 'fileinfo'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "Chýba rozšírenie: $ext";
            }
        }

        // 3. Časová zóna
        $activeTimezone = date_default_timezone_get();
        $configuredTimezone = AppTimezone::fromEnvironment();
        if (!ini_get('date.timezone') && $activeTimezone === 'UTC' && $configuredTimezone !== 'UTC') {
            $issues[] = 'PHP beží v UTC — nastavte APP_TIMEZONE alebo date.timezone v php.ini';
        }

        $status = empty($issues) ? HealthStatus::STATUS_PASS : HealthStatus::STATUS_WARN;
        $message = empty($issues) ? 'Systém je v poriadku' : implode(', ', $issues);

        $check = new HealthStatus($this->getName(), $status, $message);
        $check->setData([
            'php_version' => $phpVersion,
            'required_version' => $requiredVersion,
            'extensions' => $requiredExtensions,
            'php_timezone' => $activeTimezone,
            'php_time' => date('Y-m-d H:i:s'),
            'utc_time' => gmdate('Y-m-d H:i:s'),
            'configured_timezone' => $configuredTimezone,
            'issues' => $issues,
        ]);
        $check->setDuration(microtime(true) - $start);

        return $check;
    }
}
