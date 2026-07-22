<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

/**
 * Symetrické šifrovanie tajomstiev „at-rest" (audit A1).
 *
 * Používa 32-bajtový kľúč odvodený z `APP_KEY` a autentifikované šifrovanie:
 *  - preferovane **libsodium** `crypto_secretbox` (XSalsa20-Poly1305), formát `enc:s1:`,
 *  - fallback **OpenSSL AES-256-GCM**, formát `enc:g1:` (ak libsodium nie je dostupné).
 *
 * Slúži na šifrovanie citlivých polí ukladaných do flat-file úložiska:
 * `twoFactorSecret` (TOTP seed), SMTP heslo, SSO client secrety, ntfy/telegram
 * tokeny.
 *
 * Vlastnosti:
 *  - **Transparentná migrácia:** `decrypt()` vráti hodnoty bez známeho `enc:*`
 *    prefixu nezmenené → existujúce plaintext dáta fungujú ďalej; nové zápisy
 *    sa šifrujú.
 *  - **Idempotencia:** `encrypt()` už zašifrovanú hodnotu znovu nešifruje.
 *  - **Krížová kompatibilita:** `decrypt()` rozpozná oba formáty (`s1`/`g1`)
 *    podľa prefixu, nezávisle od toho, ktorý backend je práve preferovaný.
 *  - **Fail-safe rollout:** ak `APP_KEY` nie je platný 32-bajtový kľúč (alebo
 *    nie je dostupný žiadny crypto backend), šifrovanie je vypnuté (plaintext)
 *    a aktivuje sa nastavením reálneho `APP_KEY` – bez migračného skriptu.
 */
final class EncryptionService
{
    /** Spoločný prefix pre detekciu zašifrovaných hodnôt. */
    public const PREFIX = 'enc:';

    private const SODIUM_PREFIX = 'enc:s1:';
    private const OPENSSL_PREFIX = 'enc:g1:';

    private const KEY_BYTES = 32;      // 256-bit kľúč (secretbox aj AES-256)
    private const SODIUM_NONCE_BYTES = 24;
    private const GCM_IV_BYTES = 12;
    private const GCM_TAG_BYTES = 16;
    private const GCM_CIPHER = 'aes-256-gcm';

    private ?string $key;

    public function __construct(?string $appKey)
    {
        $this->key = $this->resolveKey($appKey);
    }

    /**
     * Šifrovanie je aktívne len ak máme platný kľúč a aspoň jeden crypto backend.
     */
    public function isEnabled(): bool
    {
        return $this->key !== null && ($this->hasSodium() || $this->hasOpenssl());
    }

    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::SODIUM_PREFIX)
            || str_starts_with($value, self::OPENSSL_PREFIX);
    }

    public function encrypt(string $plain): string
    {
        if ($plain === '' || !$this->isEnabled() || $this->isEncrypted($plain)) {
            return $plain;
        }

        /** @var string $key */
        $key = $this->key;

        if ($this->hasSodium()) {
            $nonce = random_bytes(self::SODIUM_NONCE_BYTES);
            $cipher = sodium_crypto_secretbox($plain, $nonce, $key);

            return self::SODIUM_PREFIX . base64_encode($nonce . $cipher);
        }

        // OpenSSL AES-256-GCM fallback.
        $iv = random_bytes(self::GCM_IV_BYTES);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::GCM_CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            return $plain;
        }

        return self::OPENSSL_PREFIX . base64_encode($iv . $tag . $cipher);
    }

    public function decrypt(string $value): string
    {
        // Legacy / plaintext hodnota – vráť nezmenené (transparentná migrácia).
        if (!$this->isEncrypted($value)) {
            return $value;
        }

        if ($this->key === null) {
            // Máme ciphertext, ale žiadny kľúč → nič bezpečné nevieme vrátiť.
            return '';
        }

        /** @var string $key */
        $key = $this->key;

        if (str_starts_with($value, self::SODIUM_PREFIX)) {
            return $this->decryptSodium(substr($value, strlen(self::SODIUM_PREFIX)), $key);
        }

        return $this->decryptOpenssl(substr($value, strlen(self::OPENSSL_PREFIX)), $key);
    }

    public function encryptNullable(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return $plain;
        }

        return $this->encrypt($plain);
    }

    public function decryptNullable(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $this->decrypt($value);
    }

    private function decryptSodium(string $payload, string $key): string
    {
        if (!$this->hasSodium()) {
            return '';
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) <= self::SODIUM_NONCE_BYTES) {
            return '';
        }

        $nonce = substr($decoded, 0, self::SODIUM_NONCE_BYTES);
        $cipher = substr($decoded, self::SODIUM_NONCE_BYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);

        return $plain === false ? '' : $plain;
    }

    private function decryptOpenssl(string $payload, string $key): string
    {
        if (!$this->hasOpenssl()) {
            return '';
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) <= self::GCM_IV_BYTES + self::GCM_TAG_BYTES) {
            return '';
        }

        $iv = substr($decoded, 0, self::GCM_IV_BYTES);
        $tag = substr($decoded, self::GCM_IV_BYTES, self::GCM_TAG_BYTES);
        $cipher = substr($decoded, self::GCM_IV_BYTES + self::GCM_TAG_BYTES);
        $plain = openssl_decrypt($cipher, self::GCM_CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain === false ? '' : $plain;
    }

    private function hasSodium(): bool
    {
        return function_exists('sodium_crypto_secretbox');
    }

    private function hasOpenssl(): bool
    {
        return function_exists('openssl_encrypt');
    }

    /**
     * Odvodí 32-bajtový kľúč z `APP_KEY`. Podporuje `base64:` prefix (Laravel-style),
     * 64 hex znakov, alebo surový 32-bajtový reťazec. Neplatný/prázdny kľúč → null.
     */
    private function resolveKey(?string $appKey): ?string
    {
        $appKey = trim((string) $appKey);
        if ($appKey === '') {
            return null;
        }

        $material = $appKey;
        if (str_starts_with($appKey, 'base64:')) {
            $body = substr($appKey, 7);

            // Predvídateľný placeholder (napr. base64:xxxx... alebo jeden
            // opakovaný znak) je horší než žiadny kľúč → považuj za nenastavený.
            if ($body === '' || preg_match('/^(.)\1*$/', $body) === 1) {
                return null;
            }

            $decoded = base64_decode($body, true);
            if ($decoded === false) {
                return null;
            }
            $material = $decoded;
        } elseif (preg_match('/^[0-9a-f]{64}$/i', $appKey) === 1) {
            // 64 hex znakov = 32 bajtov.
            $hex = hex2bin($appKey);
            $material = $hex === false ? $appKey : $hex;
        }

        // Placeholder alebo nesprávna dĺžka → vypnuté (napr. base64:xxxx...).
        if (strlen($material) !== self::KEY_BYTES) {
            return null;
        }

        return $material;
    }
}
