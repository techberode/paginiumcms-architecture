<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file ACL rules (Iteration 11).
 *
 * Storage: `data/security/acl.json`
 *
 * @phpstan-type AclRule array{
 *     id: string,
 *     path: string,
 *     roles: list<string>,
 *     permissions: list<string>,
 *     enabled: bool
 * }
 */
final class AclRepository
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/security/acl.json'
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    public function isEnabled(): bool
    {
        $data = $this->readStore();

        return ($data['enabled'] ?? false) === true;
    }

    /**
     * @return list<AclRule>
     */
    public function rules(): array
    {
        $data = $this->readStore();
        $rules = $data['rules'] ?? [];

        if (!is_array($rules)) {
            return [];
        }

        $normalized = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $normalized[] = $this->normalizeRule($rule);
        }

        return $normalized;
    }

    /**
     * @param list<AclRule>|array<int, mixed> $rules
     * @return array{enabled: bool, rules: list<AclRule>}
     */
    public function save(bool $enabled, array $rules): array
    {
        $normalized = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $normalized[] = $this->normalizeRule($rule);
        }

        $payload = [
            'enabled' => $enabled,
            'rules' => $normalized,
            'updated_at' => date('c'),
        ];

        $this->writeStore($payload);

        return $payload;
    }

    /**
     * @param array<string, mixed> $rule
     * @return AclRule
     */
    private function normalizeRule(array $rule): array
    {
        $roles = [];
        foreach ($rule['roles'] ?? [] as $role) {
            if (is_string($role) && $role !== '') {
                $roles[] = strtoupper($role);
            }
        }

        $permissions = [];
        foreach ($rule['permissions'] ?? [] as $permission) {
            if (is_string($permission) && $permission !== '') {
                $permissions[] = $permission;
            }
        }

        return [
            'id' => (string) ($rule['id'] ?? uniqid('acl_', true)),
            'path' => trim((string) ($rule['path'] ?? '')),
            'roles' => array_values(array_unique($roles)),
            'permissions' => array_values(array_unique($permissions)),
            'enabled' => ($rule['enabled'] ?? true) === true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readStore(): array
    {
        if (!is_readable($this->absolutePath)) {
            return ['enabled' => false, 'rules' => []];
        }

        $raw = file_get_contents($this->absolutePath);
        if ($raw === false || trim($raw) === '') {
            return ['enabled' => false, 'rules' => []];
        }

        return $this->normalizeStore(JsonHelper::decode($raw));
    }

    /**
     * @param array<int|string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizeStore(array $decoded): array
    {
        $rules = $decoded['rules'] ?? [];

        return [
            'enabled' => ($decoded['enabled'] ?? false) === true,
            'rules' => is_array($rules) ? $rules : [],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeStore(array $payload): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create ACL directory: ' . $dir);
        }

        file_put_contents(
            $this->absolutePath,
            JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
