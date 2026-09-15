<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Teams\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Flat-file teams at data/teams/{id}.json (`team@1`, It.93k).
 * Grouping + Support agent pool — does not replace RBAC.
 */
final class TeamRepository
{
    public const SCHEMA = 'team@1';

    public const TYPE_EDITORIAL = 'editorial';
    public const TYPE_SUPPORT = 'support';
    public const TYPE_OPS = 'ops';
    public const TYPE_CUSTOM = 'custom';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_EDITORIAL,
        self::TYPE_SUPPORT,
        self::TYPE_OPS,
        self::TYPE_CUSTOM,
    ];

    public const MAX_TEAMS = 80;
    public const MAX_MEMBERS = 40;
    public const MAX_NAME = 80;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativeDir = 'data/teams',
    ) {
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return self::TYPES;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(?string $type = null): array
    {
        $filter = $type !== null && $type !== '' ? $this->normalizeType($type) : null;
        $items = [];
        foreach ($this->teamFiles() as $file) {
            $id = basename($file, '.json');
            $team = $this->readNormalized($id);
            if ($team === null) {
                continue;
            }
            if ($filter !== null && $team['type'] !== $filter) {
                continue;
            }
            $items[] = $team;
        }

        usort(
            $items,
            static function (array $a, array $b): int {
                $typeCmp = strcmp((string) $a['type'], (string) $b['type']);
                if ($typeCmp !== 0) {
                    return $typeCmp;
                }

                return strcmp((string) $a['name'], (string) $b['name']);
            }
        );

        return $items;
    }

    /**
     * Unique member user ids across teams of the given type (Support desk pool).
     *
     * @return list<string>
     */
    public function memberIdsForType(string $type): array
    {
        $wanted = $this->normalizeType($type);
        $ids = [];
        foreach ($this->list($wanted) as $team) {
            $members = $team['memberUserIds'] ?? [];
            if (!is_array($members)) {
                continue;
            }
            foreach ($members as $memberId) {
                if (is_string($memberId) && $memberId !== '') {
                    $ids[$memberId] = true;
                }
            }
        }

        $list = array_keys($ids);
        sort($list);

        return $list;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        try {
            return $this->readNormalized($this->normalizeId($id));
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param list<string> $memberUserIds
     * @return array<string, mixed>
     */
    public function create(string $name, string $type, array $memberUserIds): array
    {
        if (count($this->list()) >= self::MAX_TEAMS) {
            throw new InvalidArgumentException('Too many teams.');
        }

        $id = 'team_' . bin2hex(random_bytes(5));
        $now = time();
        $type = $this->normalizeType($type);

        return $this->writeRecord([
            'schema' => self::SCHEMA,
            'id' => $id,
            'name' => $this->resolveName($name, $type),
            'type' => $type,
            'memberUserIds' => $this->normalizeMemberIds($memberUserIds),
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        $id = $this->normalizeId($id);
        $existing = $this->readNormalized($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Team not found');
        }

        if (array_key_exists('type', $payload)) {
            $existing['type'] = $this->normalizeType(is_string($payload['type']) ? $payload['type'] : '');
        }
        if (array_key_exists('name', $payload) || array_key_exists('type', $payload)) {
            $nameInput = array_key_exists('name', $payload)
                ? (is_string($payload['name']) ? $payload['name'] : '')
                : (string) ($existing['name'] ?? '');
            $existing['name'] = $this->resolveName($nameInput, (string) $existing['type']);
        }
        if (array_key_exists('memberUserIds', $payload)) {
            $raw = $payload['memberUserIds'];
            $existing['memberUserIds'] = $this->normalizeMemberIds(is_array($raw) ? $raw : []);
        }
        $existing['updatedAt'] = time();

        return $this->writeRecord($existing);
    }

    public function delete(string $id): void
    {
        $id = $this->normalizeId($id);
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new InvalidArgumentException('Team not found');
        }

        $this->writer->delete($relativePath, false);
    }

    /**
     * Drop a user from every team (user delete / anonymize).
     */
    public function removeUser(string $userId): void
    {
        $userId = trim($userId);
        if ($userId === '') {
            return;
        }

        foreach ($this->list() as $team) {
            $ids = $team['memberUserIds'] ?? [];
            if (!is_array($ids) || !in_array($userId, $ids, true)) {
                continue;
            }
            $kept = [];
            foreach ($ids as $memberId) {
                if (is_string($memberId) && $memberId !== $userId) {
                    $kept[] = $memberId;
                }
            }
            $this->update((string) $team['id'], ['memberUserIds' => $kept]);
        }
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function writeRecord(array $record): array
    {
        $id = $this->normalizeId((string) ($record['id'] ?? ''));
        $canonical = [
            'schema' => self::SCHEMA,
            'id' => $id,
            'name' => $this->normalizeName((string) ($record['name'] ?? '')),
            'type' => $this->normalizeType((string) ($record['type'] ?? '')),
            'memberUserIds' => $this->normalizeMemberIds(
                is_array($record['memberUserIds'] ?? null) ? $record['memberUserIds'] : []
            ),
            'createdAt' => is_int($record['createdAt'] ?? null) ? $record['createdAt'] : time(),
            'updatedAt' => is_int($record['updatedAt'] ?? null) ? $record['updatedAt'] : time(),
        ];

        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->relativePath($id),
            JsonHelper::encode($canonical, JSON_PRETTY_PRINT),
            false
        );

        $saved = $this->readNormalized($id);
        if ($saved === null) {
            throw new RuntimeException('Team was not stored.');
        }

        return $saved;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readNormalized(string $id): ?array
    {
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        try {
            $data = JsonHelper::decode($this->reader->read($relativePath));
        } catch (\Throwable) {
            return null;
        }

        $storedId = is_string($data['id'] ?? null) ? $data['id'] : $id;
        if ($storedId !== $id) {
            return null;
        }

        $rawMembers = $data['memberUserIds'] ?? [];
        $members = is_array($rawMembers) ? $rawMembers : [];

        try {
            return [
                'schema' => self::SCHEMA,
                'id' => $id,
                'name' => $this->normalizeName(is_string($data['name'] ?? null) ? $data['name'] : ''),
                'type' => $this->normalizeType(is_string($data['type'] ?? null) ? $data['type'] : self::TYPE_CUSTOM),
                'memberUserIds' => $this->normalizeMemberIds($members),
                'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
                'updatedAt' => is_int($data['updatedAt'] ?? null) ? $data['updatedAt'] : 0,
            ];
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function teamFiles(): array
    {
        try {
            $files = $this->reader->listFiles($this->relativeDir, '*.json');
        } catch (\Throwable) {
            return [];
        }

        $names = [];
        foreach ($files as $file) {
            if (str_ends_with($file, '.json')) {
                $names[] = $file;
            }
        }

        return $names;
    }

    private function relativePath(string $id): string
    {
        return $this->relativeDir . '/' . $id . '.json';
    }

    private function normalizeId(string $id): string
    {
        $id = strtolower(trim($id));
        if ($id === '' || !preg_match('/^team_[a-f0-9]{10}$/', $id)) {
            throw new InvalidArgumentException('Invalid team id.');
        }

        return $id;
    }

    private function resolveName(string $name, string $type): string
    {
        $name = LogSanitizer::value(trim($name), self::MAX_NAME);
        if ($name !== '') {
            return $name;
        }
        if ($type === self::TYPE_CUSTOM) {
            throw new InvalidArgumentException('Team name is required.');
        }

        return $this->defaultNameForType($type);
    }

    private function defaultNameForType(string $type): string
    {
        return match ($type) {
            self::TYPE_EDITORIAL => 'Editorial',
            self::TYPE_SUPPORT => 'Support',
            self::TYPE_OPS => 'Ops',
            default => 'Team',
        };
    }

    private function normalizeName(string $name): string
    {
        $name = LogSanitizer::value(trim($name), self::MAX_NAME);
        if ($name === '') {
            throw new InvalidArgumentException('Team name is required.');
        }

        return $name;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Invalid team type.');
        }

        return $type;
    }

    /**
     * @param array<int|string, mixed> $raw
     * @return list<string>
     */
    private function normalizeMemberIds(array $raw): array
    {
        $ids = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }
            $id = trim($value);
            if ($id === '' || !preg_match('/^[a-zA-Z0-9_.-]{1,80}$/', $id)) {
                continue;
            }
            $ids[$id] = true;
        }

        $list = array_keys($ids);
        if (count($list) > self::MAX_MEMBERS) {
            throw new InvalidArgumentException('Too many team members.');
        }

        return $list;
    }
}
