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
        private BackupOffsiteArchiveCipher $archiveCipher,
    ) {
    }

    public function isConfigured(): bool
    {
        $group = $this->settings->group('backups');

        return (bool) ($group['offsiteWebDavEnabled'] ?? false)
            && trim((string) ($group['offsiteWebDavUrl'] ?? '')) !== ''
            && trim((string) ($group['offsiteWebDavUsername'] ?? '')) !== '';
    }

    public function isEnabled(): bool
    {
        $group = $this->settings->group('backups');

        return $this->isConfigured()
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
        $uploadPath = $localZipPath;
        $contentType = 'application/zip';
        $encryptedTemp = null;

        if ((bool) ($group['offsiteEncryptEnabled'] ?? false)) {
            $encryptionKey = trim((string) ($group['offsiteEncryptionKey'] ?? ''));
            if ($encryptionKey === '') {
                $this->logFailure($remoteFileName, 'offsiteEncryptEnabled but offsiteEncryptionKey is empty');

                return ['uploaded' => false, 'error' => 'missing_encryption_key'];
            }

            try {
                $encrypted = $this->archiveCipher->encryptZipFile($localZipPath, $remoteFileName, $encryptionKey);
                $uploadPath = $encrypted['path'];
                $remoteFileName = $encrypted['remoteName'];
                $encryptedTemp = $encrypted['path'];
                $contentType = 'application/octet-stream';
            } catch (\Throwable $e) {
                $this->logFailure($remoteFileName, $e->getMessage());

                return ['uploaded' => false, 'error' => 'encrypt_failed'];
            }
        }

        $segments = $relativeDir !== '' ? explode('/', $relativeDir) : [];
        $segments[] = $remoteFileName;
        $encodedPath = implode('/', array_map('rawurlencode', $segments));
        $targetUrl = $baseUrl . '/' . $encodedPath;

        try {
            $this->guard->assertAllowed($targetUrl);
        } catch (\Throwable $e) {
            if ($encryptedTemp !== null && is_file($encryptedTemp)) {
                @unlink($encryptedTemp);
            }
            $this->logFailure($remoteFileName, $e->getMessage());

            return ['uploaded' => false, 'error' => 'url_blocked'];
        }

        $size = filesize($uploadPath);
        if ($size === false) {
            $this->cleanupTemp($encryptedTemp);

            return ['uploaded' => false, 'error' => 'stat_failed'];
        }

        if (PHP_SAPI === 'cli') {
            @set_time_limit(0);
        }

        $put = $this->webDavPut($targetUrl, $uploadPath, $size, $username, $password, $contentType);
        if (!$put['ok'] && in_array($put['http'], [404, 409], true)) {
            $mkcolError = $this->ensureRemoteCollection($baseUrl, $username, $password, $prefix);
            if ($mkcolError !== null) {
                $this->cleanupTemp($encryptedTemp);
                $this->logFailure($remoteFileName, $mkcolError);

                return ['uploaded' => false, 'error' => $mkcolError];
            }
            $put = $this->webDavPut($targetUrl, $uploadPath, $size, $username, $password, $contentType);
        }

        $this->cleanupTemp($encryptedTemp);

        if ($put['ok']) {
            error_log(sprintf(
                '[PaginiumCMS] backup offsite upload OK (%s)',
                LogSanitizer::value($remoteFileName)
            ));

            return ['uploaded' => true, 'remoteUrl' => $targetUrl];
        }

        $message = $put['error'] !== '' ? $put['error'] : ('HTTP ' . $put['http']);
        $this->logFailure($remoteFileName, $message);

        return ['uploaded' => false, 'error' => $message];
    }

    /**
     * Read-only diagnostics for CLI / support (no secrets returned).
     *
     * @return array<string, mixed>
     */
    public function diagnose(): array
    {
        $group = $this->settings->group('backups');
        $baseUrl = rtrim(trim((string) ($group['offsiteWebDavUrl'] ?? '')), '/');
        $username = trim((string) ($group['offsiteWebDavUsername'] ?? ''));
        $password = $this->resolvePassword($group);
        $encrypt = (bool) ($group['offsiteEncryptEnabled'] ?? false);
        $encKey = trim((string) ($group['offsiteEncryptionKey'] ?? ''));

        $out = [
            'enabled' => (bool) ($group['offsiteWebDavEnabled'] ?? false),
            'ready' => $this->isEnabled(),
            'urlConfigured' => $baseUrl !== '',
            'usernameConfigured' => $username !== '',
            'passwordConfigured' => $password !== '',
            'encryptEnabled' => $encrypt,
            'encryptionKeyConfigured' => !$encrypt || $encKey !== '',
            'sodiumAvailable' => function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')
                && function_exists('sodium_crypto_pwhash'),
            'urlAllowed' => null,
            'urlBlockReason' => null,
        ];

        if ($baseUrl !== '') {
            try {
                $this->guard->assertAllowed($baseUrl);
                $out['urlAllowed'] = true;
            } catch (\Throwable $e) {
                $out['urlAllowed'] = false;
                $out['urlBlockReason'] = $e->getMessage();
            }
        }

        if ($password === '' && $this->settings->hasOverride('backups', 'offsiteWebDavPassword')) {
            $out['passwordHint'] = 'Stored WebDAV password could not be decrypted (check APP_KEY in cron/CLI .env).';
        }

        if ($encrypt && !$out['sodiumAvailable']) {
            $out['encryptHint'] = 'offsiteEncryptEnabled requires PHP ext-sodium in this runtime.';
        }

        return $out;
    }

    /**
     * Optional smoke upload (tiny payload) to verify WebDAV credentials.
     *
     * @return array{uploaded: bool, error?: string}
     */
    public function probeUpload(): array
    {
        if (!$this->isEnabled()) {
            return ['uploaded' => false, 'error' => 'not_configured'];
        }

        $temp = tempnam(sys_get_temp_dir(), 'paginium-offsite-probe-');
        if ($temp === false) {
            return ['uploaded' => false, 'error' => 'temp_failed'];
        }

        $name = 'paginium-probe-' . gmdate('Y-m-d-His') . '.txt';
        file_put_contents($temp, 'PaginiumCMS off-site probe ' . gmdate('c'));

        try {
            return $this->uploadZipIfConfigured($temp, $name);
        } finally {
            @unlink($temp);
        }
    }

    private function cleanupTemp(?string $encryptedTemp): void
    {
        if ($encryptedTemp !== null && is_file($encryptedTemp)) {
            @unlink($encryptedTemp);
        }
    }

    /**
     * @return array{ok: bool, http: int, error: string}
     */
    private function webDavPut(
        string $targetUrl,
        string $uploadPath,
        int $size,
        string $username,
        string $password,
        string $contentType,
    ): array {
        $handle = fopen($uploadPath, 'rb');
        if ($handle === false) {
            return ['ok' => false, 'http' => 0, 'error' => 'open_failed'];
        }

        $curl = curl_init($targetUrl);
        if ($curl === false) {
            fclose($handle);

            return ['ok' => false, 'http' => 0, 'error' => 'curl_init_failed'];
        }

        curl_setopt_array($curl, [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $handle,
            CURLOPT_INFILESIZE => $size,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => ['Content-Type: ' . $contentType],
            CURLOPT_TIMEOUT => max(120, (int) min(7200, (int) ($size / (256 * 1024)) + 120)),
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        fclose($handle);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['ok' => true, 'http' => $httpCode, 'error' => ''];
        }

        $message = $curlError !== ''
            ? $curlError
            : ('HTTP ' . $httpCode . (is_string($body) && $body !== '' ? ' ' . substr($body, 0, 200) : ''));

        return ['ok' => false, 'http' => $httpCode, 'error' => $message];
    }

    /**
     * @param array<string, mixed> $group
     */
    private function resolvePassword(array $group): string
    {
        $password = trim((string) ($group['offsiteWebDavPassword'] ?? ''));

        return $password;
    }

    private function ensureRemoteCollection(string $baseUrl, string $username, string $password, string $prefix): ?string
    {
        if ($prefix === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $prefix)), static fn (string $s): bool => $s !== ''));
        $current = rtrim($baseUrl, '/');

        foreach ($segments as $segment) {
            $current .= '/' . rawurlencode($segment);
            $code = $this->webDavMkcol($current, $username, $password);
            if (!in_array($code, [201, 405, 409, 207], true)) {
                return sprintf('mkcol_failed: HTTP %d for %s', $code, $segment);
            }
        }

        return null;
    }

    private function webDavMkcol(string $collectionUrl, string $username, string $password): int
    {
        $curl = curl_init($collectionUrl);
        if ($curl === false) {
            return 0;
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'MKCOL',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $httpCode;
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
