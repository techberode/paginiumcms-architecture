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
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_CUSTOM = 'custom';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_EDITORIAL,
        self::TYPE_SUPPORT,
        self::TYPE_OPS,
        self::TYPE_EXTERNAL,
        self::TYPE_CUSTOM,
    ];

    public const MAX_TEAMS = 80;
    public const MAX_MEMBERS = 40;
    public const MAX_NAME = 80;
    public const MAX_TEAM_CHAT_SHARE_TARGETS = 8;

    public const MAX_TEAM_LEADERS = 5;

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
    /**
     * Team ids the user belongs to (any type).
     *
     * @return list<string>
     */
    public function idsForMember(string $userId): array
    {
        $userId = trim($userId);
        if ($userId === '') {
            return [];
        }

        $ids = [];
        foreach ($this->list() as $team) {
            $members = $team['memberUserIds'] ?? [];
            if (!is_array($members) || !in_array($userId, $members, true)) {
                continue;
            }
            $ids[] = (string) $team['id'];
        }

        sort($ids);

        return $ids;
    }

    /**
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
        if (array_key_exists('teamLeaderUserIds', $payload)) {
            $leaderMembers = is_array($existing['memberUserIds'] ?? null)
                ? $this->normalizeMemberIds($existing['memberUserIds'])
                : [];
            $existing['teamLeaderUserIds'] = $this->normalizeTeamLeaderIds(
                is_array($payload['teamLeaderUserIds']) ? $payload['teamLeaderUserIds'] : [],
                $leaderMembers
            );
        }
        if (array_key_exists('chatEnabled', $payload)) {
            $existing['chatEnabled'] = (bool) $payload['chatEnabled'];
        }
        if (array_key_exists('teamChatEnabled', $payload)) {
            $existing['teamChatEnabled'] = (bool) $payload['teamChatEnabled'];
        }
        if (array_key_exists('teamChatShareEnabled', $payload)) {
            $existing['teamChatShareEnabled'] = (bool) $payload['teamChatShareEnabled'];
        }
        if (array_key_exists('teamChatShareWithTeamIds', $payload)) {
            $existing['teamChatShareWithTeamIds'] = $this->normalizeTeamChatShareWithTeamIds(
                (string) $existing['id'],
                is_array($payload['teamChatShareWithTeamIds']) ? $payload['teamChatShareWithTeamIds'] : []
            );
        }
        if (array_key_exists('replyMailEnabled', $payload)) {
            $existing['replyMailEnabled'] = (bool) $payload['replyMailEnabled'];
        }
        if (array_key_exists('replyMail', $payload)) {
            $existing['replyMail'] = $this->normalizeReplyMail((string) $payload['replyMail']);
        }
        if (array_key_exists('color', $payload)) {
            $existing['color'] = $this->normalizeColor((string) $payload['color']);
        }
        if (array_key_exists('kanbanEnabled', $payload)) {
            $existing['kanbanEnabled'] = (bool) $payload['kanbanEnabled'];
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
            $leaders = is_array($team['teamLeaderUserIds'] ?? null) ? $team['teamLeaderUserIds'] : [];
            $keptLeaders = [];
            foreach ($leaders as $leaderId) {
                if (is_string($leaderId) && $leaderId !== $userId && in_array($leaderId, $kept, true)) {
                    $keptLeaders[] = $leaderId;
                }
            }
            $this->update((string) $team['id'], [
                'memberUserIds' => $kept,
                'teamLeaderUserIds' => $keptLeaders,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function writeRecord(array $record): array
    {
        $id = $this->normalizeId((string) ($record['id'] ?? ''));
        $memberUserIds = $this->normalizeMemberIds(
            is_array($record['memberUserIds'] ?? null) ? $record['memberUserIds'] : []
        );
        $canonical = [
            'schema' => self::SCHEMA,
            'id' => $id,
            'name' => $this->normalizeName((string) ($record['name'] ?? '')),
            'type' => $this->normalizeType((string) ($record['type'] ?? '')),
            'memberUserIds' => $memberUserIds,
            'teamLeaderUserIds' => $this->normalizeTeamLeaderIds(
                is_array($record['teamLeaderUserIds'] ?? null) ? $record['teamLeaderUserIds'] : [],
                $memberUserIds
            ),
            'chatEnabled' => array_key_exists('chatEnabled', $record)
                ? (bool) $record['chatEnabled']
                : ($this->normalizeType((string) ($record['type'] ?? '')) === self::TYPE_SUPPORT),
            'teamChatEnabled' => array_key_exists('teamChatEnabled', $record)
                ? (bool) $record['teamChatEnabled']
                : $this->defaultTeamChatEnabled($this->normalizeType((string) ($record['type'] ?? ''))),
            'teamChatShareEnabled' => (bool) ($record['teamChatShareEnabled'] ?? false),
            'teamChatShareWithTeamIds' => $this->normalizeTeamChatShareWithTeamIds(
                $id,
                is_array($record['teamChatShareWithTeamIds'] ?? null) ? $record['teamChatShareWithTeamIds'] : []
            ),
            'replyMailEnabled' => (bool) ($record['replyMailEnabled'] ?? false),
            'replyMail' => $this->normalizeReplyMail((string) ($record['replyMail'] ?? '')),
            'color' => $this->normalizeColor((string) ($record['color'] ?? '')),
            'kanbanEnabled' => array_key_exists('kanbanEnabled', $record)
                ? (bool) $record['kanbanEnabled']
                : ($this->normalizeType((string) ($record['type'] ?? '')) === self::TYPE_SUPPORT),
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
            $memberUserIds = $this->normalizeMemberIds($members);

            return [
                'schema' => self::SCHEMA,
                'id' => $id,
                'name' => $this->normalizeName(is_string($data['name'] ?? null) ? $data['name'] : ''),
                'type' => $this->normalizeType(is_string($data['type'] ?? null) ? $data['type'] : self::TYPE_CUSTOM),
                'memberUserIds' => $memberUserIds,
                'teamLeaderUserIds' => $this->normalizeTeamLeaderIds(
                    is_array($data['teamLeaderUserIds'] ?? null) ? $data['teamLeaderUserIds'] : [],
                    $memberUserIds
                ),
                'chatEnabled' => array_key_exists('chatEnabled', $data)
                    ? (bool) $data['chatEnabled']
                    : ($this->normalizeType(is_string($data['type'] ?? null) ? $data['type'] : self::TYPE_CUSTOM) === self::TYPE_SUPPORT),
                'teamChatEnabled' => array_key_exists('teamChatEnabled', $data)
                    ? (bool) $data['teamChatEnabled']
                    : $this->defaultTeamChatEnabled(
                        $this->normalizeType(is_string($data['type'] ?? null) ? $data['type'] : self::TYPE_CUSTOM)
                    ),
                'teamChatShareEnabled' => (bool) ($data['teamChatShareEnabled'] ?? false),
                'teamChatShareWithTeamIds' => $this->normalizeTeamChatShareWithTeamIds(
                    $id,
                    is_array($data['teamChatShareWithTeamIds'] ?? null) ? $data['teamChatShareWithTeamIds'] : []
                ),
                'replyMailEnabled' => (bool) ($data['replyMailEnabled'] ?? false),
                'replyMail' => $this->normalizeReplyMail(is_string($data['replyMail'] ?? null) ? $data['replyMail'] : ''),
                'color' => $this->normalizeColor(is_string($data['color'] ?? null) ? $data['color'] : ''),
                'kanbanEnabled' => array_key_exists('kanbanEnabled', $data)
                    ? (bool) $data['kanbanEnabled']
                    : ($this->normalizeType(is_string($data['type'] ?? null) ? $data['type'] : self::TYPE_CUSTOM) === self::TYPE_SUPPORT),
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

    private function defaultTeamChatEnabled(string $type): bool
    {
        return $type === self::TYPE_EXTERNAL;
    }

    /**
     * @param array<int|string, mixed> $raw
     * @return list<string>
     */
    private function normalizeTeamChatShareWithTeamIds(string $ownerTeamId, array $raw): array
    {
        $ownerTeamId = $this->normalizeId($ownerTeamId);
        $ids = [];
        foreach ($raw as $entry) {
            if (!is_string($entry) || $entry === '') {
                continue;
            }
            try {
                $id = $this->normalizeId($entry);
            } catch (InvalidArgumentException) {
                continue;
            }
            if ($id === $ownerTeamId || $this->get($id) === null) {
                continue;
            }
            $ids[$id] = true;
            if (count($ids) >= self::MAX_TEAM_CHAT_SHARE_TARGETS) {
                break;
            }
        }

        $list = array_keys($ids);
        sort($list);

        return $list;
    }

    private function normalizeColor(string $color): string
    {
        $color = strtolower(trim($color));
        if ($color === '') {
            return '';
        }
        if (!preg_match('/^#[0-9a-f]{6}$/', $color)) {
            throw new InvalidArgumentException('Invalid team color.');
        }

        return $color;
    }

    private function normalizeReplyMail(string $email): string
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return '';
        }

        return LogSanitizer::value($email, 255);
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
        if ($type === self::TYPE_CUSTOM || $type === self::TYPE_EXTERNAL) {
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
            self::TYPE_EXTERNAL => 'External',
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
     * @param list<string> $memberUserIds
     * @return list<string>
     */
    private function normalizeTeamLeaderIds(array $raw, array $memberUserIds): array
    {
        $memberSet = array_fill_keys($memberUserIds, true);
        $ids = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }
            $id = trim($value);
            if ($id === '' || !isset($memberSet[$id])) {
                continue;
            }
            $ids[$id] = true;
            if (count($ids) >= self::MAX_TEAM_LEADERS) {
                break;
            }
        }

        $list = array_keys($ids);
        sort($list);

        return $list;
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
