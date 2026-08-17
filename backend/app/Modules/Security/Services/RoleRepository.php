<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\RoleRecord;
use PaginiumCMS\Modules\Security\PermissionCatalog;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file RBAC registry at data/roles.json (It.84d).
 */
final class RoleRepository
{
    /** @var list<string> */
    public const SYSTEM_ROLE_IDS = [
        AuthorizationInterface::ROLE_ADMIN,
        AuthorizationInterface::ROLE_EDITOR,
        AuthorizationInterface::ROLE_USER,
    ];

    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $registryFile = 'data/roles.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/')
            . '/' . ltrim($this->registryFile, '/');
    }

    /**
     * @return list<array{id: string, name: string, permissions: list<string>, system: bool}>
     */
    public function list(): array
    {
        $records = $this->readAll();
        $items = [];
        foreach ($records as $record) {
            $items[] = $record->toArray();
        }

        usort($items, static function (array $a, array $b): int {
            if ($a['system'] !== $b['system']) {
                return $b['system'] <=> $a['system'];
            }

            return strcmp((string) $a['id'], (string) $b['id']);
        });

        return $items;
    }

    /**
     * @return array<string, list<string>>
     */
    public function permissionsMap(): array
    {
        $map = [];
        foreach ($this->readAll() as $id => $record) {
            $map[$id] = PermissionCatalog::normalizeList($record->permissions);
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public function assignableIds(): array
    {
        $ids = array_keys($this->readAll());
        sort($ids);

        return $ids;
    }

    public function exists(string $id): bool
    {
        $id = RoleRecord::normalizeId($id);

        return $id !== '' && isset($this->readAll()[$id]);
    }

    public function isSystemRole(string $id): bool
    {
        $id = RoleRecord::normalizeId($id);

        return in_array($id, self::SYSTEM_ROLE_IDS, true);
    }

    public function get(string $id): ?RoleRecord
    {
        $id = RoleRecord::normalizeId($id);
        if ($id === '') {
            return null;
        }

        return $this->readAll()[$id] ?? null;
    }

    /**
     * @param list<string> $permissions
     */
    public function save(string $id, string $name, array $permissions, bool $system = false): RoleRecord
    {
        $id = RoleRecord::normalizeId($id);
        if ($id === '' || RoleRecord::isReservedId($id)) {
            throw new RuntimeException('Invalid role id.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Role name is required.');
        }

        $permissions = PermissionCatalog::normalizeList($permissions);
        if ($permissions === []) {
            throw new RuntimeException('Role must include at least one permission.');
        }

        $existing = $this->get($id);
        $isSystem = $existing?->system === true || $system || $this->isSystemRole($id);

        $record = new RoleRecord($id, $name, $permissions, $isSystem);

        $this->withLockedRegistry(function (array &$records) use ($record): void {
            $records[$record->id] = $record;
        });

        return $record;
    }

    public function delete(string $id): void
    {
        $id = RoleRecord::normalizeId($id);
        if ($id === '' || RoleRecord::isReservedId($id)) {
            throw new RuntimeException('Invalid role id.');
        }

        if ($this->isSystemRole($id)) {
            throw new RuntimeException('System roles cannot be deleted.');
        }

        $this->withLockedRegistry(function (array &$records) use ($id): void {
            if (!isset($records[$id])) {
                throw new RuntimeException('Role not found.');
            }

            unset($records[$id]);
        });
    }

    /**
     * @return array<string, RoleRecord>
     */
    private function readAll(): array
    {
        return $this->withLockedRegistry(static fn (array $records): array => $records);
    }

    /**
     * @template T
     * @param callable(array<string, RoleRecord>&): T $callback
     * @return T
     */
    private function withLockedRegistry(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open role registry: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock role registry.');
            }

            $records = $this->readRecords($handle);
            $before = $this->serialize($records);
            $result = $callback($records);
            $after = $this->serialize($records);

            if ($after !== $before) {
                $this->writeRecords($handle, $records);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return array<string, RoleRecord>
     */
    private function readRecords($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($raw);
        } catch (\JsonException) {
            return [];
        }

        $records = [];
        foreach ($decoded as $id => $entry) {
            if (!is_string($id) || !is_array($entry)) {
                continue;
            }
            $normalizedId = RoleRecord::normalizeId($id);
            if ($normalizedId === '' || RoleRecord::isReservedId($normalizedId)) {
                continue;
            }
            $records[$normalizedId] = RoleRecord::fromArray($normalizedId, $entry);
        }

        return $records;
    }

    /**
     * @param resource $handle
     * @param array<string, RoleRecord> $records
     */
    private function writeRecords($handle, array $records): void
    {
        $payload = [];
        foreach ($records as $id => $record) {
            $payload[$id] = $record->toArray();
        }
        ksort($payload);

        $json = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json);
        fflush($handle);
    }

    /**
     * @param array<string, RoleRecord> $records
     */
    private function serialize(array $records): string
    {
        $payload = [];
        foreach ($records as $id => $record) {
            $payload[$id] = $record->toArray();
        }
        ksort($payload);

        return JsonHelper::encode($payload);
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->registryFile);
        if ($dir !== '' && $dir !== '.') {
            $this->writer->createDirectory($dir);
        }
    }
}
