<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file SSOT for API keys — stores verifiers only, never plaintext secrets (It.74).
 *
 * @phpstan-type ApiKeyRecord array{
 *     id: string,
 *     label: string,
 *     secretVerifier: string,
 *     scopes: list<string>,
 *     createdAt: string,
 *     expiresAt: string|null,
 *     lastUsedAt: string|null,
 *     revokedAt: string|null,
 *     createdBy: string,
 *     idPrefix: string
 * }
 */
final class ApiKeyStore
{
    private string $absolutePath;

    /** @var list<string> */
    public const READ_SCOPES = ['content:read', 'media:read', 'settings:read'];

    /** @var list<string> */
    public const WRITE_SCOPES = ['content:write', 'media:write', 'git:publish'];

    /** @var list<string> */
    public const TOKEN_SCOPES = ['token:issue'];

    /** @var list<string> */
    public const ALL_SCOPES = [
        'content:read',
        'media:read',
        'settings:read',
        'content:write',
        'media:write',
        'git:publish',
        'token:issue',
    ];

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/api-keys.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @param list<string> $scopes
     * @return array{record: ApiKeyRecord, secret: string, token: string}
     */
    public function create(string $label, array $scopes, ?string $expiresAt, string $createdBy, ApiKeyVerifier $verifier): array
    {
        $normalizedScopes = $this->normalizeScopes($scopes);
        if ($normalizedScopes === []) {
            throw new \InvalidArgumentException('At least one valid scope is required');
        }

        $id = bin2hex(random_bytes(8));
        $secret = $this->generateSecret();
        $token = ApiKeyVerifier::formatToken($id, $secret);
        $now = gmdate('c');

        $record = [
            'id' => $id,
            'label' => trim($label) !== '' ? trim($label) : 'API key',
            'secretVerifier' => $verifier->hashSecret($secret),
            'scopes' => $normalizedScopes,
            'createdAt' => $now,
            'expiresAt' => $expiresAt,
            'lastUsedAt' => null,
            'revokedAt' => null,
            'createdBy' => $createdBy,
            'idPrefix' => 'pgk_' . $id,
        ];

        $this->withLockedStore(function (array $store) use ($record): array {
            $keys = is_array($store['keys'] ?? null) ? $store['keys'] : [];
            $keys[$record['id']] = $record;
            $store['schemaVersion'] = 1;
            $store['keys'] = $keys;

            return $store;
        });

        return [
            'record' => $record,
            'secret' => $secret,
            'token' => $token,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listMetadata(): array
    {
        $rows = [];

        $this->withLockedStore(function (array $store) use (&$rows): array {
            $keys = is_array($store['keys'] ?? null) ? $store['keys'] : [];
            foreach ($keys as $key) {
                if (!is_array($key)) {
                    continue;
                }
                $normalized = $this->normalizeRecord($key);
                if ($normalized === null) {
                    continue;
                }
                $rows[] = $this->toPublicMetadata($normalized);
            }

            return $store;
        });

        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? '')));

        return $rows;
    }

    /**
     * @return ApiKeyRecord|null
     */
    public function findById(string $id): ?array
    {
        $found = null;

        $this->withLockedStore(function (array $store) use ($id, &$found): array {
            $key = $store['keys'][$id] ?? null;
            $found = is_array($key) ? $this->normalizeRecord($key) : null;

            return $store;
        });

        return $found;
    }

    public function revoke(string $id): bool
    {
        $updated = false;

        $this->withLockedStore(function (array $store) use ($id, &$updated): array {
            if (!isset($store['keys'][$id]) || !is_array($store['keys'][$id])) {
                return $store;
            }

            $store['keys'][$id]['revokedAt'] = gmdate('c');
            $updated = true;

            return $store;
        });

        return $updated;
    }

    /**
     * Revokes the existing key and creates a replacement with the same label/scopes/expiry.
     *
     * @return array{record: ApiKeyRecord, secret: string, token: string, previousId: string}|null
     */
    public function rotate(string $id, string $createdBy, ApiKeyVerifier $verifier): ?array
    {
        $existing = $this->findById($id);
        if ($existing === null || $existing['revokedAt'] !== null) {
            return null;
        }

        $this->revoke($id);

        $created = $this->create(
            $existing['label'],
            $existing['scopes'],
            $existing['expiresAt'],
            $createdBy,
            $verifier
        );

        return [
            'record' => $created['record'],
            'secret' => $created['secret'],
            'token' => $created['token'],
            'previousId' => $id,
        ];
    }

    public function touchLastUsed(string $id): void
    {
        $this->withLockedStore(function (array $store) use ($id): array {
            if (!isset($store['keys'][$id]) || !is_array($store['keys'][$id])) {
                return $store;
            }

            $previous = $store['keys'][$id]['lastUsedAt'] ?? null;
            $now = time();
            if (is_string($previous) && $previous !== '') {
                $prevTs = strtotime($previous) ?: 0;
                if ($now - $prevTs < 300) {
                    return $store;
                }
            }

            $store['keys'][$id]['lastUsedAt'] = gmdate('c');

            return $store;
        });
    }

    /**
     * @param list<string> $scopes
     * @return list<string>
     */
    private function normalizeScopes(array $scopes): array
    {
        $allowed = array_fill_keys(self::ALL_SCOPES, true);
        $normalized = [];
        foreach ($scopes as $scope) {
            if (!isset($allowed[$scope])) {
                continue;
            }
            $normalized[$scope] = $scope;
        }

        return array_values($normalized);
    }

    /**
     * @param array<string, mixed> $record
     * @return ApiKeyRecord|null
     */
    private function normalizeRecord(array $record): ?array
    {
        foreach (['id', 'label', 'secretVerifier', 'createdAt', 'createdBy', 'idPrefix'] as $field) {
            if (!isset($record[$field]) || !is_string($record[$field]) || $record[$field] === '') {
                return null;
            }
        }

        if (!isset($record['scopes']) || !is_array($record['scopes'])) {
            return null;
        }

        $rawScopes = [];
        foreach ($record['scopes'] as $scope) {
            if (is_string($scope)) {
                $rawScopes[] = $scope;
            }
        }

        $scopes = $this->normalizeScopes($rawScopes);
        if ($scopes === []) {
            return null;
        }

        return [
            'id' => $record['id'],
            'label' => $record['label'],
            'secretVerifier' => $record['secretVerifier'],
            'scopes' => $scopes,
            'createdAt' => $record['createdAt'],
            'expiresAt' => isset($record['expiresAt']) && is_string($record['expiresAt']) ? $record['expiresAt'] : null,
            'lastUsedAt' => isset($record['lastUsedAt']) && is_string($record['lastUsedAt']) ? $record['lastUsedAt'] : null,
            'revokedAt' => isset($record['revokedAt']) && is_string($record['revokedAt']) ? $record['revokedAt'] : null,
            'createdBy' => $record['createdBy'],
            'idPrefix' => $record['idPrefix'],
        ];
    }

    /**
     * @param ApiKeyRecord $record
     * @return array<string, mixed>
     */
    private function toPublicMetadata(array $record): array
    {
        $status = 'active';
        if ($record['revokedAt'] !== null) {
            $status = 'revoked';
        } elseif ($record['expiresAt'] !== null && strtotime($record['expiresAt']) !== false && strtotime($record['expiresAt']) < time()) {
            $status = 'expired';
        }

        return [
            'id' => $record['id'],
            'idPrefix' => $record['idPrefix'],
            'label' => $record['label'],
            'scopes' => $record['scopes'],
            'status' => $status,
            'createdAt' => $record['createdAt'],
            'expiresAt' => $record['expiresAt'],
            'lastUsedAt' => $record['lastUsedAt'],
            'revokedAt' => $record['revokedAt'],
            'createdBy' => $record['createdBy'],
        ];
    }

    private function generateSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    private function withLockedStore(callable $callback): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create API key store directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open API key store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock API key store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: [])
                : ['schemaVersion' => 1, 'keys' => []];

            if (!is_array($store)) {
                $store = ['schemaVersion' => 1, 'keys' => []];
            }

            $store = $callback($store);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, JsonHelper::encode($store, JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
