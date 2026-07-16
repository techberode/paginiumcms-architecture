<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services\Checkers;

use PaginiumCMS\Core\Health\Contracts\HealthCheckInterface;
use PaginiumCMS\Core\Health\Models\HealthStatus;

class SecurityChecker implements HealthCheckInterface
{
    public function getName(): string { return 'security'; }
    public function getDescription(): string { return 'Kontrola bezpečnostných nastavení'; }
    public function getGroup(): string { return 'security'; }

    public function check(): HealthStatus
    {
        $start = microtime(true);
        $issues = [];
        $data = [];

        // 1. Debug mód
        $debug = getenv('APP_DEBUG') === 'true';
        $data['debug_mode'] = $debug;
        if ($debug) {
            $issues[] = 'Debug mód je zapnutý (APP_DEBUG=true)';
        }

        // 2. HTTPS
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $data['https'] = $https;
        if (!$https) {
            $issues[] = 'HTTPS nie je aktívny';
        }

        // 3. Session nastavenia
        $sessionSecure = ini_get('session.cookie_secure');
        $data['session_secure'] = $sessionSecure;
        if ($sessionSecure != 1 && $https) {
            $issues[] = 'session.cookie_secure nie je zapnutý';
        }

        $sessionHttpOnly = ini_get('session.cookie_httponly');
        $data['session_httponly'] = $sessionHttpOnly;
        if ($sessionHttpOnly != 1) {
            $issues[] = 'session.cookie_httponly nie je zapnutý';
        }

        // 4. .env súbor
        $envExists = file_exists(__DIR__ . '/../../../../.env');
        $data['env_exists'] = $envExists;
        if (!$envExists) {
            $issues[] = '.env súbor neexistuje';
        }

        // 5. Zakázané funkcie
        $disabledFunctions = ini_get('disable_functions');
        $disabledFunctions = is_string($disabledFunctions) ? $disabledFunctions : '';
        $data['disabled_functions'] = $disabledFunctions;
        $dangerousFunctions = ['exec', 'shell_exec', 'system', 'passthru'];
        $found = [];
        foreach ($dangerousFunctions as $func) {
            if (strpos($disabledFunctions, $func) === false) {
                $found[] = $func;
            }
        }
        if (!empty($found)) {
            $issues[] = 'Nebezpečné funkcie nie sú zakázané: ' . implode(', ', $found);
        }

        $status = empty($issues) ? HealthStatus::STATUS_PASS : HealthStatus::STATUS_WARN;
        $message = empty($issues) ? 'Bezpečnostné nastavenia sú v poriadku' : implode(', ', $issues);

        $check = new HealthStatus($this->getName(), $status, $message);
        $check->setData($data);
        $check->setDuration(microtime(true) - $start);

        return $check;
    }
}
