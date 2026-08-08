<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

/**
 * HS256 short-lived JWT for headless delegation (It.74 Phase 74b).
 */
final class ApiJwtService
{
    public const ISSUER = 'paginiumcms';
    public const AUDIENCE = 'paginium-headless';
    public const MAX_TTL_SECONDS = 900;

    public function __construct(
        private ApiJwtDenylistStore $denylist,
        private string $signingKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->signingKey !== '';
    }

    public function looksLikeJwt(string $token): bool
    {
        $token = trim($token);
        if (!str_starts_with($token, 'eyJ')) {
            return false;
        }

        return substr_count($token, '.') === 2;
    }

    /**
     * @param list<string> $scopes
     */
    public function issue(array $scopes, string $subject, int $ttlSeconds, ?string $keyId = null): string
    {
        $this->assertConfigured();
        if ($scopes === []) {
            throw new \InvalidArgumentException('At least one scope is required');
        }

        $now = time();
        $ttl = max(1, min($ttlSeconds, self::MAX_TTL_SECONDS));
        $jti = bin2hex(random_bytes(16));

        $payload = [
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'sub' => $subject,
            'jti' => $jti,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'scope' => implode(' ', $scopes),
        ];

        if ($keyId !== null && $keyId !== '') {
            $payload['key_id'] = $keyId;
        }

        return $this->encode($payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $token): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        try {
            $header = json_decode($this->base64UrlDecode($headerB64), true, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($this->base64UrlDecode($payloadB64), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($header) || !is_array($payload)) {
            return null;
        }

        if (($header['alg'] ?? '') !== 'HS256' || ($header['typ'] ?? '') !== 'JWT') {
            return null;
        }

        $expected = $this->base64UrlEncode(hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $this->signingKey, true));
        if (!hash_equals($expected, $signatureB64)) {
            return null;
        }

        $now = time();
        if (($payload['iss'] ?? '') !== self::ISSUER) {
            return null;
        }

        if (($payload['aud'] ?? '') !== self::AUDIENCE) {
            return null;
        }

        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp <= $now) {
            return null;
        }

        $nbf = (int) ($payload['nbf'] ?? 0);
        if ($nbf > $now) {
            return null;
        }

        $jti = (string) ($payload['jti'] ?? '');
        if ($jti === '' || $this->denylist->isDenied($jti)) {
            return null;
        }

        $scope = trim((string) ($payload['scope'] ?? ''));
        if ($scope === '') {
            return null;
        }

        $payload['scope_list'] = array_values(array_filter(explode(' ', $scope), static fn (string $s): bool => $s !== ''));

        return $payload;
    }

    public function revoke(string $token): bool
    {
        $claims = $this->verify($token);
        if ($claims === null) {
            return false;
        }

        $jti = (string) ($claims['jti'] ?? '');
        $exp = (int) ($claims['exp'] ?? 0);
        if ($jti === '' || $exp <= 0) {
            return false;
        }

        $this->denylist->deny($jti, $exp);

        return true;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $payloadB64, $this->signingKey, true));

        return $header . '.' . $payloadB64 . '.' . $signature;
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('API_JWT_KEY is not configured');
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
