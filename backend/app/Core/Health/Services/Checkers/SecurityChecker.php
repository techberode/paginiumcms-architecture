<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services\Checkers;

use PaginiumCMS\Core\Health\Contracts\HealthCheckInterface;
use PaginiumCMS\Core\Health\Models\HealthStatus;
use PaginiumCMS\Core\Health\Services\HealthRuntimeContext;
use PaginiumCMS\Core\Security\ClientIpResolver;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\RequestHttpsDetector;

class SecurityChecker implements HealthCheckInterface
{
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
    }

    public function getName(): string { return 'security'; }
    public function getDescription(): string { return 'Kontrola bezpečnostných nastavení'; }
    public function getGroup(): string { return 'security'; }

    public function check(): HealthStatus
    {
        $start = microtime(true);
        $issues = [];
        $data = [];

        $server = HealthRuntimeContext::serverParams();
        $trustedProxies = ClientIpResolver::trustedProxiesFromEnv();
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $data['app_env'] = $appEnv;
        $data['trusted_proxies_configured'] = $trustedProxies !== [];

        // 1. Debug mód
        $debug = getenv('APP_DEBUG') === 'true' || filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN);
        $data['debug_mode'] = $debug;
        if ($debug && $appEnv === 'production') {
            $issues[] = 'Debug mód je zapnutý (APP_DEBUG=true) v produkcii';
        }

        // 2. HTTPS — current inbound request (admin dashboard uses same TLS termination as operators)
        $httpsProbe = RequestHttpsDetector::detect($server, $trustedProxies);
        $data['https'] = $httpsProbe['secure'];
        $data['https_source'] = $httpsProbe['source'];

        $siteUrl = (string) ($this->settings->get('general.siteUrl') ?? '');
        $data['site_url'] = $siteUrl;
        $siteExpectsHttps = str_starts_with(strtolower(trim($siteUrl)), 'https://');

        if (!$httpsProbe['secure'] && $appEnv === 'production') {
            if ($siteExpectsHttps && $httpsProbe['source'] === 'none' && $trustedProxies !== []) {
                $issues[] = 'HTTPS: siteUrl je https, ale request nemá HTTPS ani dôveryhodný X-Forwarded-Proto (nastavte proxy hlavičku a TRUSTED_PROXIES)';
            } elseif ($siteExpectsHttps) {
                $issues[] = 'HTTPS: siteUrl je https, ale aktuálny request nie je detegovaný ako TLS';
            } else {
                $issues[] = 'HTTPS nie je aktívny pre aktuálny request';
            }
        }

        // 3. Session nastavenia
        $sessionSecure = ini_get('session.cookie_secure');
        $data['session_secure'] = $sessionSecure;
        if ($sessionSecure != 1 && $httpsProbe['secure']) {
            $issues[] = 'session.cookie_secure nie je zapnutý';
        }

        $sessionHttpOnly = ini_get('session.cookie_httponly');
        $data['session_httponly'] = $sessionHttpOnly;
        if ($sessionHttpOnly != 1) {
            $issues[] = 'session.cookie_httponly nie je zapnutý';
        }

        // 4. .env súbor (backend/ or repo root — same as bootstrap/app.php)
        $envExists = $this->envFileExists();
        $data['env_exists'] = $envExists;
        if (!$envExists && $appEnv !== 'testing') {
            $issues[] = '.env súbor neexistuje (backend/ ani koreň projektu)';
        }

        // 5. Zakázané funkcie — odporúčanie len v produkcii
        $disabledFunctions = ini_get('disable_functions');
        $disabledFunctions = is_string($disabledFunctions) ? $disabledFunctions : '';
        $data['disabled_functions'] = $disabledFunctions;
        if ($appEnv === 'production') {
            $dangerousFunctions = ['exec', 'shell_exec', 'system', 'passthru'];
            $found = [];
            foreach ($dangerousFunctions as $func) {
                if ($disabledFunctions === '' || strpos($disabledFunctions, $func) === false) {
                    $found[] = $func;
                }
            }
            if ($found !== []) {
                $issues[] = 'Nebezpečné funkcie nie sú zakázané v php.ini: ' . implode(', ', $found);
            }
        }

        $status = empty($issues) ? HealthStatus::STATUS_PASS : HealthStatus::STATUS_WARN;
        $message = empty($issues) ? 'Bezpečnostné nastavenia sú v poriadku' : implode(', ', $issues);

        $check = new HealthStatus($this->getName(), $status, $message);
        $check->setData($data);
        $check->setDuration(microtime(true) - $start);

        return $check;
    }

    private function backendRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    private function envFileExists(): bool
    {
        $backendRoot = $this->backendRoot();
        $projectRoot = dirname($backendRoot);

        return is_file($backendRoot . '/.env') || is_file($projectRoot . '/.env');
    }
}
