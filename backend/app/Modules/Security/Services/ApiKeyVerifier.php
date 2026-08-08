<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\ApiKeyContext;

/**
 * Parses and verifies pgk_* API keys using HMAC-SHA-256 and API_KEY_PEPPER (It.74).
 */
final class ApiKeyVerifier
{
    private const PREFIX = 'pgk_';

    public function __construct(
        private ApiKeyStore $store,
        private string $pepper,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->pepper !== '';
    }

    public function hashSecret(string $secret): string
    {
        $this->assertConfigured();

        return hash_hmac('sha256', $secret, $this->pepper);
    }

    /**
     * @return array{id: string, secret: string}|null
     */
    public function parseToken(string $token): ?array
    {
        $token = trim($token);
        if (!str_starts_with($token, self::PREFIX)) {
            return null;
        }

        $remainder = substr($token, strlen(self::PREFIX));
        $underscore = strpos($remainder, '_');
        if ($underscore === false || $underscore < 8) {
            return null;
        }

        $id = substr($remainder, 0, $underscore);
        $secret = substr($remainder, $underscore + 1);

        if ($secret === '' || !preg_match('/^[a-f0-9]{16}$/', $id)) {
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9_-]{32,128}$/', $secret)) {
            return null;
        }

        return ['id' => $id, 'secret' => $secret];
    }

    public function looksLikeApiKey(string $authorizationHeader): bool
    {
        $token = $this->extractBearerToken($authorizationHeader);

        return $token !== null && str_starts_with($token, self::PREFIX);
    }

    public function verifyBearer(string $authorizationHeader): ?ApiKeyContext
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $token = $this->extractBearerToken($authorizationHeader);
        if ($token === null) {
            return null;
        }

        $parsed = $this->parseToken($token);
        if ($parsed === null) {
            return null;
        }

        $record = $this->store->findById($parsed['id']);
        if ($record === null) {
            return null;
        }

        if ($record['revokedAt'] !== null) {
            return null;
        }

        if ($record['expiresAt'] !== null) {
            $expires = strtotime($record['expiresAt']);
            if ($expires !== false && $expires < time()) {
                return null;
            }
        }

        $expected = $record['secretVerifier'];
        $actual = $this->hashSecret($parsed['secret']);
        if (!hash_equals($expected, $actual)) {
            return null;
        }

        $this->store->touchLastUsed($record['id']);

        return new ApiKeyContext(
            $record['id'],
            $record['label'],
            $record['scopes'],
        );
    }

    public static function formatToken(string $id, string $secret): string
    {
        return self::PREFIX . $id . '_' . $secret;
    }

    private function extractBearerToken(string $authorizationHeader): ?string
    {
        $authorizationHeader = trim($authorizationHeader);
        if ($authorizationHeader === '') {
            return null;
        }

        if (!preg_match('/^Bearer\s+(\S+)/i', $authorizationHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('API_KEY_PEPPER is not configured');
        }
    }
}
