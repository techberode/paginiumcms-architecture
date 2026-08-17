<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Models;

/**
 * Flat-file RBAC role row (It.84d).
 *
 * @phpstan-type RoleRecordArray array{
 *     id: string,
 *     name: string,
 *     permissions: list<string>,
 *     system: bool
 * }
 */
final class RoleRecord
{
    /**
     * @param list<string> $permissions
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $permissions,
        public readonly bool $system = false,
    ) {
    }

    /**
     * @return RoleRecordArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions,
            'system' => $this->system,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $id, array $data): self
    {
        $normalizedId = self::normalizeId($id);
        $name = trim((string) ($data['name'] ?? $normalizedId));
        $permissions = is_array($data['permissions'] ?? null)
            ? array_values(array_map(static fn (mixed $entry): string => (string) $entry, $data['permissions']))
            : [];
        $system = ($data['system'] ?? false) === true;

        return new self(
            $normalizedId,
            $name !== '' ? $name : $normalizedId,
            $permissions,
            $system,
        );
    }

    public static function normalizeId(string $id): string
    {
        $id = strtoupper(trim($id));
        if ($id === '' || !preg_match('/^[A-Z][A-Z0-9_]{1,31}$/', $id)) {
            return '';
        }

        return $id;
    }

    public static function isReservedId(string $id): bool
    {
        return self::normalizeId($id) === 'SUPER_ADMIN';
    }
}
