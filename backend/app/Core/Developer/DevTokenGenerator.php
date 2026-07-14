<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Developer;

/**
 * Offline generátor a validátor dev unlock tokenov.
 *
 * Dizajn s minimálnym zaťažením CMS:
 * - Žiadne volania GitHub API za behu.
 * - Token sa generuje mimo produkcie (CLI / CI v privátnom repozitári).
 * - V CMS sa ukladá len SHA-256 hash tokenu v gitignore súbore
 *   storage/dev/registered_tokens.json.
 * - Overenie = hash + expirácia + jednorazové použitie (voliteľné).
 *
 * Formát tokenu: pagdev_{base64url(payload)}.{hmac}
 */
class DevTokenGenerator
{
    private const PREFIX = 'pagdev_';

    public function __construct(private string $secret)
    {
        if ($secret === '') {
            throw new \InvalidArgumentException('DEV_UNLOCK_SECRET nie je nastavený');
        }
    }

    /**
     * @return array{token: string, hash: string, expires_at: int, label: string}
     */
    public function generate(string $label = 'developer', int $ttlSeconds = 86400, bool $singleUse = true): array
    {
        $expiresAt = time() + $ttlSeconds;
        $payload = [
            'label' => $label,
            'exp' => $expiresAt,
            'single' => $singleUse,
            'nonce' => bin2hex(random_bytes(8)),
        ];

        $payloadB64 = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $payloadB64, $this->secret);
        $token = self::PREFIX . $payloadB64 . '.' . $signature;
        $hash = hash('sha256', $token);

        return [
            'token' => $token,
            'hash' => $hash,
            'expires_at' => $expiresAt,
            'label' => $label,
        ];
    }

    /**
     * @return array{valid: bool, label?: string, reason?: string}
     */
    public function validate(string $token, DevTokenRegistry $registry): array
    {
        if (!str_starts_with($token, self::PREFIX)) {
            return ['valid' => false, 'reason' => 'Neplatný formát tokenu'];
        }

        $parts = explode('.', substr($token, strlen(self::PREFIX)), 2);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'Neplatná štruktúra tokenu'];
        }

        [$payloadB64, $signature] = $parts;
        $expected = hash_hmac('sha256', $payloadB64, $this->secret);
        if (!hash_equals($expected, $signature)) {
            return ['valid' => false, 'reason' => 'Neplatný podpis tokenu'];
        }

        try {
            $payload = json_decode($this->base64UrlDecode($payloadB64), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['valid' => false, 'reason' => 'Neplatný payload'];
        }

        if (($payload['exp'] ?? 0) < time()) {
            return ['valid' => false, 'reason' => 'Token expiroval'];
        }

        $hash = hash('sha256', $token);
        $entry = $registry->findByHash($hash);
        if ($entry === null) {
            return ['valid' => false, 'reason' => 'Token nie je registrovaný (spustite registráciu v CI / CLI)'];
        }

        if (!empty($entry['revoked'])) {
            return ['valid' => false, 'reason' => 'Token bol zrušený'];
        }

        if (!empty($entry['single_use']) && !empty($entry['used_at'])) {
            return ['valid' => false, 'reason' => 'Token už bol použitý'];
        }

        return ['valid' => true, 'label' => $entry['label'] ?? ($payload['label'] ?? 'developer')];
    }

    /**
     * Overí podpis a expiráciu bez registrácie (pre CLI register).
     *
     * @return array{valid: bool, payload?: array<string, mixed>, reason?: string}
     */
    public function verifyStructure(string $token): array
    {
        if (!str_starts_with($token, self::PREFIX)) {
            return ['valid' => false, 'reason' => 'Neplatný formát tokenu'];
        }

        $parts = explode('.', substr($token, strlen(self::PREFIX)), 2);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'Neplatná štruktúra tokenu'];
        }

        [$payloadB64, $signature] = $parts;
        $expected = hash_hmac('sha256', $payloadB64, $this->secret);
        if (!hash_equals($expected, $signature)) {
            return ['valid' => false, 'reason' => 'Neplatný podpis tokenu'];
        }

        try {
            $payload = json_decode($this->base64UrlDecode($payloadB64), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['valid' => false, 'reason' => 'Neplatný payload'];
        }

        if (($payload['exp'] ?? 0) < time()) {
            return ['valid' => false, 'reason' => 'Token expiroval'];
        }

        return ['valid' => true, 'payload' => $payload];
    }

    public function markUsed(string $token, DevTokenRegistry $registry): void
    {
        $registry->markUsed(hash('sha256', $token));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
