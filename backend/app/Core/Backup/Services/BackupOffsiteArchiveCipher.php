<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Security\Services\EncryptionUnavailableException;
use RuntimeException;

/**
 * Encrypts backup ZIP bytes for off-site upload using a key separate from APP_KEY (libsodium secretbox).
 */
final class BackupOffsiteArchiveCipher
{
    private const PREFIX = 'enc:bk:s1:';
    private const KEY_BYTES = 32;
    private const NONCE_BYTES = 24;

    /**
     * @return array{path: string, remoteName: string}
     */
    public function encryptZipFile(string $plainZipPath, string $remoteBaseName, string $passphrase): array
    {
        if ($passphrase === '') {
            throw new InvalidArgumentException('Off-site encryption passphrase is required.');
        }

        if (!function_exists('sodium_crypto_secretbox')) {
            throw EncryptionUnavailableException::forCipherFailure();
        }

        $plain = file_get_contents($plainZipPath);
        if ($plain === false || $plain === '') {
            throw new RuntimeException('Cannot read backup ZIP for encryption.');
        }

        $key = hash('sha256', $passphrase, true);
        if (strlen($key) !== self::KEY_BYTES) {
            throw EncryptionUnavailableException::forCipherFailure();
        }

        $nonce = random_bytes(self::NONCE_BYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, $key);

        $payload = self::PREFIX . base64_encode($nonce . $cipher);
        $remoteName = preg_replace('/\.zip$/i', '', basename($remoteBaseName)) . '.zip.enc';
        $tempPath = sys_get_temp_dir() . '/paginium-backup-offsite-' . bin2hex(random_bytes(8)) . '.enc';
        if (file_put_contents($tempPath, $payload) === false) {
            throw new RuntimeException('Failed to write encrypted backup temp file.');
        }

        return ['path' => $tempPath, 'remoteName' => $remoteName];
    }
}
