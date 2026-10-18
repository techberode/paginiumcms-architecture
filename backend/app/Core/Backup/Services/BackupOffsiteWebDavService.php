<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

use PaginiumCMS\Support\LogSanitizer;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Upload completed backup ZIPs to Nextcloud (or any WebDAV) after local create.
 */
final class BackupOffsiteWebDavService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private OutboundUrlGuard $guard,
    ) {
    }

    public function isEnabled(): bool
    {
        $group = $this->settings->group('backups');

        return (bool) ($group['offsiteWebDavEnabled'] ?? false)
            && trim((string) ($group['offsiteWebDavUrl'] ?? '')) !== ''
            && trim((string) ($group['offsiteWebDavUsername'] ?? '')) !== ''
            && $this->resolvePassword($group) !== '';
    }

    /**
     * @return array{uploaded: bool, remoteUrl?: string, error?: string}
     */
    public function uploadZipIfConfigured(string $localZipPath, string $remoteFileName): array
    {
        if (!is_file($localZipPath)) {
            return ['uploaded' => false, 'error' => 'disabled_or_missing_file'];
        }

        $group = $this->settings->group('backups');
        if (!(bool) ($group['offsiteWebDavEnabled'] ?? false)) {
            return ['uploaded' => false, 'error' => 'disabled'];
        }

        $baseUrl = rtrim(trim((string) ($group['offsiteWebDavUrl'] ?? '')), '/');
        $username = trim((string) ($group['offsiteWebDavUsername'] ?? ''));
        $password = $this->resolvePassword($group);
        $prefix = trim((string) ($group['offsiteWebDavPathPrefix'] ?? 'PaginiumCMS/backups'), '/');

        if ($baseUrl === '' || $username === '') {
            return ['uploaded' => false, 'error' => 'incomplete_config'];
        }

        if ($password === '') {
            $this->logFailure($remoteFileName, 'missing_webdav_password (check APP_KEY in cron/CLI .env)');

            return ['uploaded' => false, 'error' => 'missing_password'];
        }

        $relativeDir = $prefix;
        $remoteFileName = basename($remoteFileName);
        $segments = $relativeDir !== '' ? explode('/', $relativeDir) : [];
        $segments[] = $remoteFileName;
        $encodedPath = implode('/', array_map('rawurlencode', $segments));
        $targetUrl = $baseUrl . '/' . $encodedPath;

        try {
            $this->guard->assertAllowed($targetUrl);
        } catch (\Throwable $e) {
            $this->logFailure($remoteFileName, $e->getMessage());

            return ['uploaded' => false, 'error' => 'url_blocked'];
        }

        if (!$this->ensureRemoteCollection($baseUrl, $username, $password, $prefix)) {
            return ['uploaded' => false, 'error' => 'mkcol_failed'];
        }

        $size = filesize($localZipPath);
        if ($size === false) {
            return ['uploaded' => false, 'error' => 'stat_failed'];
        }

        if (PHP_SAPI === 'cli') {
            @set_time_limit(0);
        }

        $handle = fopen($localZipPath, 'rb');
        if ($handle === false) {
            return ['uploaded' => false, 'error' => 'open_failed'];
        }

        $curl = curl_init($targetUrl);
        if ($curl === false) {
            fclose($handle);

            return ['uploaded' => false, 'error' => 'curl_init_failed'];
        }

        curl_setopt_array($curl, [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $handle,
            CURLOPT_INFILESIZE => $size,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_HTTPHEADER => ['Content-Type: application/zip'],
            CURLOPT_TIMEOUT => max(120, (int) min(7200, (int) ($size / (256 * 1024)) + 120)),
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        fclose($handle);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log(sprintf(
                '[PaginiumCMS] backup offsite upload OK (%s)',
                LogSanitizer::value($remoteFileName)
            ));

            return ['uploaded' => true, 'remoteUrl' => $targetUrl];
        }

        $message = $curlError !== '' ? $curlError : ('HTTP ' . $httpCode . ' ' . (is_string($body) ? substr($body, 0, 200) : ''));
        $this->logFailure($remoteFileName, $message);

        return ['uploaded' => false, 'error' => $message];
    }

    /**
     * @param array<string, mixed> $group
     */
    private function resolvePassword(array $group): string
    {
        $password = trim((string) ($group['offsiteWebDavPassword'] ?? ''));

        return $password;
    }

    private function ensureRemoteCollection(string $baseUrl, string $username, string $password, string $prefix): bool
    {
        if ($prefix === '') {
            return true;
        }

        $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $prefix)), static fn (string $s): bool => $s !== ''));
        $current = rtrim($baseUrl, '/');

        foreach ($segments as $segment) {
            $current .= '/' . rawurlencode($segment);
            if (!$this->webDavMkcol($current, $username, $password)) {
                return false;
            }
        }

        return true;
    }

    private function webDavMkcol(string $collectionUrl, string $username, string $password): bool
    {
        $curl = curl_init($collectionUrl);
        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'MKCOL',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // 201 Created, 405 Method Not Allowed (exists), 409 Conflict (exists) — treat as OK.
        return in_array($httpCode, [201, 405, 409, 207], true);
    }

    private function logFailure(string $fileName, string $message): void
    {
        error_log(sprintf(
            '[PaginiumCMS] backup offsite upload failed (%s): %s',
            LogSanitizer::value($fileName),
            LogSanitizer::value($message)
        ));
    }
}
