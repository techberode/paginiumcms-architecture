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
            && trim((string) ($group['offsiteWebDavUsername'] ?? '')) !== '';
    }

    /**
     * @return array{uploaded: bool, remoteUrl?: string, error?: string}
     */
    public function uploadZipIfConfigured(string $localZipPath, string $remoteFileName): array
    {
        if (!$this->isEnabled() || !is_file($localZipPath)) {
            return ['uploaded' => false, 'error' => 'disabled_or_missing_file'];
        }

        $group = $this->settings->group('backups');
        $baseUrl = rtrim(trim((string) ($group['offsiteWebDavUrl'] ?? '')), '/');
        $username = trim((string) ($group['offsiteWebDavUsername'] ?? ''));
        $password = (string) ($group['offsiteWebDavPassword'] ?? '');
        $prefix = trim((string) ($group['offsiteWebDavPathPrefix'] ?? 'PaginiumCMS/backups'), '/');

        $segments = $prefix !== '' ? explode('/', $prefix) : [];
        $segments[] = basename($remoteFileName);
        $encodedPath = implode('/', array_map('rawurlencode', $segments));
        $targetUrl = $baseUrl . '/' . $encodedPath;

        try {
            $this->guard->assertAllowed($targetUrl);
        } catch (\Throwable $e) {
            $this->logFailure($remoteFileName, $e->getMessage());

            return ['uploaded' => false, 'error' => 'url_blocked'];
        }

        $size = filesize($localZipPath);
        if ($size === false) {
            return ['uploaded' => false, 'error' => 'stat_failed'];
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
            CURLOPT_TIMEOUT => max(120, (int) min(3600, (int) ($size / (512 * 1024)) + 60)),
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        fclose($handle);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['uploaded' => true, 'remoteUrl' => $targetUrl];
        }

        $message = $curlError !== '' ? $curlError : ('HTTP ' . $httpCode . ' ' . (is_string($body) ? substr($body, 0, 200) : ''));
        $this->logFailure($remoteFileName, $message);

        return ['uploaded' => false, 'error' => $message];
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
