<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Security\Services\EncryptionUnavailableException;
use RuntimeException;

/**
 * Encrypts backup ZIP files for off-site upload using a passphrase separate from APP_KEY.
 *
 * Format s2 (written): `enc:bk:s2:` | salt(16) | opslimit(u32 BE) | memlimit(u32 BE) | secretstream header(24)
 * | repeated [chunk length(u32 BE) | XChaCha20-Poly1305 chunk]. Key = Argon2id(passphrase, salt).
 * Streams in 64 KiB chunks, so large archives never sit fully in memory.
 *
 * Format s1 (legacy, read-only): `enc:bk:s1:` + base64(nonce | secretbox), key = SHA-256(passphrase).
 */
final class BackupOffsiteArchiveCipher
{
    public const PREFIX_V2 = 'enc:bk:s2:';
    public const PREFIX_V1 = 'enc:bk:s1:';

    private const CHUNK_BYTES = 65536;
    private const MAX_CIPHER_CHUNK = self::CHUNK_BYTES + 17;
    private const KEY_BYTES = 32;
    private const V1_NONCE_BYTES = 24;
    /** KDF bounds read from untrusted file headers (libsodium MIN … well above SENSITIVE) — caps decrypt DoS. */
    public const KDF_OPS_MIN = 1;
    public const KDF_OPS_MAX = 16;
    public const KDF_MEM_MIN = 8192;
    public const KDF_MEM_MAX = 1073741824;

    /**
     * @param int|null $opsLimit Argon2id ops limit (tests may lower it; null = libsodium INTERACTIVE)
     * @param int|null $memLimit Argon2id memory limit in bytes (null = libsodium INTERACTIVE)
     */
    public function __construct(
        private ?int $opsLimit = null,
        private ?int $memLimit = null,
    ) {
    }

    /**
     * @return array{path: string, remoteName: string}
     */
    public function encryptZipFile(string $plainZipPath, string $remoteBaseName, string $passphrase): array
    {
        if ($passphrase === '') {
            throw new InvalidArgumentException('Off-site encryption passphrase is required.');
        }
        self::assertSodium();

        $in = @fopen($plainZipPath, 'rb');
        if ($in === false) {
            throw new RuntimeException('Cannot read backup ZIP for encryption.');
        }

        $tempPath = sys_get_temp_dir() . '/paginium-backup-offsite-' . bin2hex(random_bytes(8)) . '.enc';
        $out = @fopen($tempPath, 'wb');
        if ($out === false) {
            fclose($in);
            throw new RuntimeException('Failed to write encrypted backup temp file.');
        }

        $key = '';
        try {
            $opsLimit = $this->opsLimit ?? SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
            $memLimit = $this->memLimit ?? SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;
            $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
            $key = self::deriveKey($passphrase, $salt, $opsLimit, $memLimit);
            [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);

            self::write($out, self::PREFIX_V2 . $salt . pack('NN', $opsLimit, $memLimit) . $header);

            $wroteAny = false;
            $chunk = self::readChunk($in);
            while ($chunk !== '') {
                $next = self::readChunk($in);
                $tag = $next === ''
                    ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                    : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
                $cipher = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
                self::write($out, pack('N', strlen($cipher)) . $cipher);
                $wroteAny = true;
                $chunk = $next;
            }

            if (!$wroteAny) {
                throw new RuntimeException('Cannot read backup ZIP for encryption.');
            }
        } catch (\Throwable $e) {
            fclose($out);
            @unlink($tempPath);
            throw $e;
        } finally {
            fclose($in);
            if ($key !== '') {
                sodium_memzero($key);
            }
        }

        fclose($out);
        $remoteName = preg_replace('/\.zip$/i', '', basename($remoteBaseName)) . '.zip.enc';

        return ['path' => $tempPath, 'remoteName' => $remoteName];
    }

    /**
     * Decrypts an off-site `*.zip.enc` (s2 or legacy s1) into `$plainZipPath`.
     *
     * @throws RuntimeException on wrong passphrase, truncation or tampering (output file is removed)
     */
    public function decryptToFile(string $encryptedPath, string $plainZipPath, string $passphrase): void
    {
        if ($passphrase === '') {
            throw new InvalidArgumentException('Off-site encryption passphrase is required.');
        }
        self::assertSodium();

        $in = @fopen($encryptedPath, 'rb');
        if ($in === false) {
            throw new RuntimeException('Cannot read encrypted backup.');
        }

        try {
            $prefix = (string) fread($in, strlen(self::PREFIX_V2));
            if ($prefix === self::PREFIX_V2) {
                $this->decryptV2($in, $plainZipPath, $passphrase);

                return;
            }
            if ($prefix === self::PREFIX_V1) {
                $this->decryptV1($in, $plainZipPath, $passphrase);

                return;
            }
        } finally {
            fclose($in);
        }

        throw new RuntimeException('Unknown encrypted backup format.');
    }

    /**
     * @param resource $in
     */
    private function decryptV2($in, string $plainZipPath, string $passphrase): void
    {
        $salt = self::readExact($in, SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $limits = unpack('Nops/Nmem', self::readExact($in, 8));
        $header = self::readExact($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
        if (!is_array($limits)) {
            throw new RuntimeException('Corrupted encrypted backup header.');
        }

        $key = self::deriveKey($passphrase, $salt, (int) $limits['ops'], (int) $limits['mem']);
        $out = @fopen($plainZipPath, 'wb');
        if ($out === false) {
            sodium_memzero($key);
            throw new RuntimeException('Cannot write decrypted backup.');
        }

        try {
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
            $finished = false;
            while (!$finished) {
                $lengthBytes = fread($in, 4);
                if ($lengthBytes === false || $lengthBytes === '') {
                    throw new RuntimeException('Encrypted backup is truncated.');
                }
                $length = unpack('N', str_pad($lengthBytes, 4, "\0", STR_PAD_LEFT));
                $size = is_array($length) ? (int) $length[1] : 0;
                if (strlen($lengthBytes) !== 4 || $size <= 0 || $size > self::MAX_CIPHER_CHUNK) {
                    throw new RuntimeException('Corrupted encrypted backup chunk.');
                }

                $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, self::readExact($in, $size));
                if ($result === false) {
                    throw new RuntimeException('Wrong passphrase or tampered encrypted backup.');
                }
                [$plain, $tag] = $result;
                self::write($out, $plain);
                $finished = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            }

            if (fread($in, 1) !== '') {
                throw new RuntimeException('Unexpected data after encrypted backup end.');
            }
        } catch (\Throwable $e) {
            fclose($out);
            @unlink($plainZipPath);
            throw $e;
        } finally {
            sodium_memzero($key);
        }

        fclose($out);
    }

    /**
     * @param resource $in
     */
    private function decryptV1($in, string $plainZipPath, string $passphrase): void
    {
        $raw = base64_decode((string) stream_get_contents($in), true);
        if (!is_string($raw) || strlen($raw) <= self::V1_NONCE_BYTES) {
            throw new RuntimeException('Corrupted encrypted backup.');
        }

        $key = hash('sha256', $passphrase, true);
        $plain = sodium_crypto_secretbox_open(
            substr($raw, self::V1_NONCE_BYTES),
            substr($raw, 0, self::V1_NONCE_BYTES),
            $key
        );
        sodium_memzero($key);
        if ($plain === false) {
            throw new RuntimeException('Wrong passphrase or tampered encrypted backup.');
        }

        if (file_put_contents($plainZipPath, $plain) === false) {
            throw new RuntimeException('Cannot write decrypted backup.');
        }
    }

    private static function deriveKey(string $passphrase, string $salt, int $opsLimit, int $memLimit): string
    {
        if ($opsLimit < self::KDF_OPS_MIN || $opsLimit > self::KDF_OPS_MAX
            || $memLimit < self::KDF_MEM_MIN || $memLimit > self::KDF_MEM_MAX
        ) {
            throw new RuntimeException('Invalid key derivation parameters.');
        }

        return sodium_crypto_pwhash(
            self::KEY_BYTES,
            $passphrase,
            $salt,
            $opsLimit,
            $memLimit,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    private static function assertSodium(): void
    {
        if (!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')
            || !function_exists('sodium_crypto_pwhash')
        ) {
            throw EncryptionUnavailableException::forCipherFailure();
        }
    }

    /**
     * @param resource $in
     */
    private static function readChunk($in): string
    {
        $chunk = fread($in, self::CHUNK_BYTES);

        return $chunk === false ? '' : $chunk;
    }

    /**
     * @param resource $in
     */
    private static function readExact($in, int $length): string
    {
        $data = '';
        while (($remaining = $length - strlen($data)) > 0) {
            $part = fread($in, $remaining);
            if ($part === false || $part === '') {
                throw new RuntimeException('Encrypted backup is truncated.');
            }
            $data .= $part;
        }

        return $data;
    }

    /**
     * @param resource $out
     */
    private static function write($out, string $bytes): void
    {
        if (fwrite($out, $bytes) !== strlen($bytes)) {
            throw new RuntimeException('Failed to write backup file.');
        }
    }
}
