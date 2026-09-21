<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Teams\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Discord-style room per team when {@see TeamRepository} `teamChatEnabled` is on (It.93o-7, extended).
 * Storage is isolated under `data/team-chat/{teamId}/` — Support and External never share a room.
 * Files are download-only.
 */
final class TeamChatStore
{
    public const SCHEMA = 'team-chat-message@1';

    public const KIND_TEXT = 'text';

    public const KIND_CODE = 'code';

    public const KIND_FILE = 'file';

    public const MAX_BODY = 20000;

    public const EXPORT_SCHEMA = 'team-chat-export@1';

    public const READ_SCHEMA = 'team-chat-read@1';

    public const MAX_IMPORT_MESSAGES = 500;

    public const HISTORY_ACCESS_DENIED = 'History access denied.';

    private const PREVIEW_MAX = 140;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private TeamRepository $teams,
        private UploadPolicyEngine $uploads,
        private SettingsRepositoryInterface $settings,
        private string $relativeDir = 'data/team-chat',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function roomsFor(User $actor): array
    {
        $rooms = [];
        foreach ($this->teams->list() as $team) {
            if (!$this->teamChatEnabled($team) || !$this->canAccess($team, $actor)) {
                continue;
            }
            $rooms[] = [
                'id' => $team['id'],
                'name' => $team['name'],
                'type' => (string) ($team['type'] ?? ''),
                'shared' => !$this->isDirectMember($team, $actor),
                'canManageHistory' => $this->canManageHistory($team, $actor),
            ];
        }

        return $rooms;
    }

    /**
     * Unread team-chat items for notification beacons (does not mark read).
     *
     * @return array{count: int, items: list<array<string, mixed>>}
     */
    public function inboxFor(User $actor): array
    {
        $items = [];
        $total = 0;
        foreach ($this->roomsFor($actor) as $room) {
            $teamId = (string) ($room['id'] ?? '');
            if ($teamId === '') {
                continue;
            }
            $stats = $this->unreadStats($teamId, $actor);
            if ($stats['unread'] < 1) {
                continue;
            }
            $total += $stats['unread'];
            $items[] = [
                'teamId' => $teamId,
                'teamName' => (string) ($room['name'] ?? ''),
                'teamType' => (string) ($room['type'] ?? ''),
                'shared' => (bool) ($room['shared'] ?? false),
                'unread' => $stats['unread'],
                'preview' => $stats['preview'],
                'lastMessageAt' => $stats['lastMessageAt'],
            ];
        }

        usort(
            $items,
            static fn (array $a, array $b): int => ((int) $b['lastMessageAt']) <=> ((int) $a['lastMessageAt'])
        );

        return ['count' => $total, 'items' => $items];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function messages(string $teamId, User $actor): array
    {
        $team = $this->requireAccess($teamId, $actor);
        $items = $this->listMessages((string) $team['id']);
        $watermark = 0;
        foreach ($items as $item) {
            $watermark = max($watermark, (int) ($item['createdAt'] ?? 0));
        }
        if ($watermark > 0) {
            $this->markRead((string) $team['id'], $actor->getId(), $watermark);
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $teamId, User $actor, string $query): array
    {
        $team = $this->requireAccess($teamId, $actor);
        $this->requireHistoryAccess($team, $actor);
        $query = trim($query);
        if ($query === '') {
            throw new InvalidArgumentException('Search query is required.');
        }
        $query = mb_strtolower(LogSanitizer::value($query, 120));
        $limit = $this->searchMaxResults();
        $matches = [];
        foreach ($this->listRetainedMessages((string) $team['id']) as $message) {
            $haystack = mb_strtolower(
                ((string) ($message['body'] ?? '')) . ' ' . ((string) ($message['authorName'] ?? ''))
            );
            if (!str_contains($haystack, $query)) {
                continue;
            }
            $matches[] = $message;
            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @return array{json: string, filename: string}
     */
    public function exportArchive(string $teamId, User $actor): array
    {
        $team = $this->requireAccess($teamId, $actor);
        $this->requireHistoryAccess($team, $actor);
        $teamId = (string) $team['id'];
        $payload = [
            'schema' => self::EXPORT_SCHEMA,
            'teamId' => $teamId,
            'teamName' => (string) ($team['name'] ?? ''),
            'exportedAt' => time(),
            'retentionDays' => $this->retentionDays(),
            'messages' => $this->listRetainedMessages($teamId),
        ];

        return [
            'json' => JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'filename' => 'team-chat-' . $teamId . '-' . gmdate('Y-m-d') . '.json',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function importArchive(string $teamId, User $actor, array $payload): int
    {
        $team = $this->requireAccess($teamId, $actor);
        $this->requireHistoryAccess($team, $actor);
        $teamId = (string) $team['id'];
        if (($payload['schema'] ?? '') !== self::EXPORT_SCHEMA) {
            throw new InvalidArgumentException('Unsupported export format.');
        }
        if ((string) ($payload['teamId'] ?? '') !== $teamId) {
            throw new InvalidArgumentException('Export teamId does not match this room.');
        }
        $raw = $payload['messages'] ?? null;
        if (!is_array($raw)) {
            throw new InvalidArgumentException('Export messages are missing.');
        }
        if (count($raw) > self::MAX_IMPORT_MESSAGES) {
            throw new InvalidArgumentException('Too many messages in import file.');
        }

        $existing = [];
        foreach ($this->messageFiles($teamId) as $name) {
            $existing[basename($name, '.json')] = true;
        }

        $imported = 0;
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $id = is_string($entry['id'] ?? null) ? $entry['id'] : '';
            if ($id === '' || !preg_match('/^tcm_[a-f0-9]{12}$/', $id) || isset($existing[$id])) {
                continue;
            }
            $record = [
                'schema' => self::SCHEMA,
                'id' => $id,
                'teamId' => $teamId,
                'authorUserId' => is_string($entry['authorUserId'] ?? null) ? $entry['authorUserId'] : '',
                'authorName' => LogSanitizer::value((string) ($entry['authorName'] ?? ''), 120),
                'kind' => (string) ($entry['kind'] ?? self::KIND_TEXT),
                'body' => LogSanitizer::value((string) ($entry['body'] ?? ''), self::MAX_BODY),
                'language' => is_string($entry['language'] ?? null) ? $entry['language'] : '',
                'file' => is_array($entry['file'] ?? null) ? $entry['file'] : null,
                'createdAt' => is_numeric($entry['createdAt'] ?? null) ? (int) $entry['createdAt'] : time(),
            ];
            $dir = $this->relativeDir . '/' . $teamId . '/messages';
            $this->writer->createDirectory($dir);
            $this->writer->write(
                $dir . '/' . $id . '.json',
                JsonHelper::encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                false
            );
            $existing[$id] = true;
            $imported++;
        }

        $this->prune($teamId);

        return $imported;
    }

    public function clearHistory(string $teamId, User $actor): int
    {
        $team = $this->requireAccess($teamId, $actor);
        $this->requireHistoryAccess($team, $actor);
        $teamId = (string) $team['id'];
        $deleted = 0;
        foreach ($this->messageFiles($teamId) as $name) {
            try {
                $this->writer->delete($this->relativeDir . '/' . $teamId . '/messages/' . $name, false);
                $deleted++;
            } catch (\Throwable) {
            }
        }
        $readDir = $this->relativeDir . '/' . $teamId . '/read';
        try {
            foreach ($this->reader->listFiles($readDir, '*.json') as $name) {
                $base = basename($name);
                if ($base !== '') {
                    $this->writer->delete($readDir . '/' . $base, false);
                }
            }
        } catch (\Throwable) {
        }

        return $deleted;
    }

    /**
     * @param array<string, mixed> $team
     */
    public function canManageHistory(array $team, User $actor): bool
    {
        if ($actor->hasRole('SUPER_ADMIN')) {
            return true;
        }
        $leaders = $team['teamLeaderUserIds'] ?? [];
        if (!is_array($leaders)) {
            return false;
        }

        return in_array($actor->getId(), $leaders, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function post(string $teamId, User $actor, string $kind, string $body, string $language = ''): array
    {
        $team = $this->requireAccess($teamId, $actor);
        $kind = $kind === self::KIND_CODE ? self::KIND_CODE : self::KIND_TEXT;
        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Message body is required.');
        }
        $body = LogSanitizer::value($body, self::MAX_BODY);
        $language = strtolower(preg_replace('/[^a-z0-9+#.-]/', '', $language) ?? '');

        return $this->writeMessage((string) $team['id'], $actor, [
            'kind' => $kind,
            'body' => $body,
            'language' => $kind === self::KIND_CODE ? $language : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function attach(string $teamId, User $actor, string $filename, string $binary, string $mime): array
    {
        $team = $this->requireAccess($teamId, $actor);
        $validatedMime = $this->uploads->enforceBinary(
            UploadSurfaceRegistry::SURFACE_TEAM_CHAT_UPLOAD,
            $filename,
            $binary,
            $mime,
            $actor->getId()
        );
        $fileId = 'file_' . bin2hex(random_bytes(8));
        $dir = $this->relativeDir . '/' . $team['id'] . '/files';
        $this->writer->createDirectory($dir);
        $this->writer->write($dir . '/' . $fileId, $binary, false);
        $this->writer->write(
            $dir . '/' . $fileId . '.meta.json',
            JsonHelper::encode([
                'id' => $fileId,
                'name' => LogSanitizer::value($filename, 180),
                'mime' => $validatedMime !== '' ? $validatedMime : 'application/octet-stream',
                'size' => strlen($binary),
            ], JSON_PRETTY_PRINT),
            false
        );

        return $this->writeMessage((string) $team['id'], $actor, [
            'kind' => self::KIND_FILE,
            'body' => LogSanitizer::value($filename, 180),
            'language' => '',
            'file' => [
                'id' => $fileId,
                'name' => LogSanitizer::value($filename, 180),
                'size' => strlen($binary),
                'mime' => $validatedMime !== '' ? $validatedMime : 'application/octet-stream',
            ],
        ]);
    }

    /**
     * @return array{name: string, mime: string, binary: string}|null
     */
    public function download(string $teamId, string $fileId, User $actor): ?array
    {
        $team = $this->requireAccess($teamId, $actor);
        if (!preg_match('/^file_[a-f0-9]{16}$/', $fileId)) {
            return null;
        }
        $relative = $this->relativeDir . '/' . $team['id'] . '/files/' . $fileId;
        if (!$this->reader->exists($relative)) {
            return null;
        }
        $meta = [];
        $metaPath = $relative . '.meta.json';
        if ($this->reader->exists($metaPath)) {
            try {
                $meta = JsonHelper::decode($this->reader->read($metaPath));
            } catch (\Throwable) {
                $meta = [];
            }
        }

        return [
            'name' => is_string($meta['name'] ?? null) ? (string) $meta['name'] : $fileId,
            'mime' => is_string($meta['mime'] ?? null) ? (string) $meta['mime'] : 'application/octet-stream',
            'binary' => $this->reader->read($relative),
        ];
    }

    /**
     * @param array<string, mixed> $team
     */
    public function canAccess(array $team, User $actor): bool
    {
        if (!$this->teamChatEnabled($team)) {
            return false;
        }

        $memberTeamIds = $this->teams->idsForMember($actor->getId());
        if ($memberTeamIds === []) {
            return false;
        }

        if ($this->isDirectMember($team, $actor)) {
            return true;
        }

        if (!$this->teamChatShareEnabled($team)) {
            return false;
        }

        foreach ($this->teamChatShareWithTeamIds($team) as $guestTeamId) {
            if (in_array($guestTeamId, $memberTeamIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $team
     */
    private function teamChatEnabled(array $team): bool
    {
        if (array_key_exists('teamChatEnabled', $team)) {
            return (bool) $team['teamChatEnabled'];
        }

        return ($team['type'] ?? '') === TeamRepository::TYPE_EXTERNAL;
    }

    /**
     * @param array<string, mixed> $team
     */
    private function isDirectMember(array $team, User $actor): bool
    {
        $members = is_array($team['memberUserIds'] ?? null) ? $team['memberUserIds'] : [];

        return in_array($actor->getId(), $members, true);
    }

    /**
     * @param array<string, mixed> $team
     */
    private function teamChatShareEnabled(array $team): bool
    {
        return (bool) ($team['teamChatShareEnabled'] ?? false);
    }

    /**
     * @param array<string, mixed> $team
     * @return list<string>
     */
    private function teamChatShareWithTeamIds(array $team): array
    {
        $raw = $team['teamChatShareWithTeamIds'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $ids = [];
        foreach ($raw as $id) {
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireAccess(string $teamId, User $actor): array
    {
        $team = $this->teams->get($teamId);
        if ($team === null || !$this->canAccess($team, $actor)) {
            throw new InvalidArgumentException('Team chat is not available.');
        }

        return $team;
    }

    /**
     * @param array<string, mixed> $team
     */
    private function requireHistoryAccess(array $team, User $actor): void
    {
        if (!$this->canManageHistory($team, $actor)) {
            throw new InvalidArgumentException(self::HISTORY_ACCESS_DENIED);
        }
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function writeMessage(string $teamId, User $actor, array $fields): array
    {
        $this->prune($teamId);
        $id = 'tcm_' . bin2hex(random_bytes(6));
        $record = [
            'schema' => self::SCHEMA,
            'id' => $id,
            'teamId' => $teamId,
            'authorUserId' => $actor->getId(),
            'authorName' => $actor->getName() !== '' ? $actor->getName() : $actor->getEmail(),
            'kind' => (string) ($fields['kind'] ?? self::KIND_TEXT),
            'body' => (string) ($fields['body'] ?? ''),
            'language' => (string) ($fields['language'] ?? ''),
            'file' => is_array($fields['file'] ?? null) ? $fields['file'] : null,
            'createdAt' => time(),
        ];
        $dir = $this->relativeDir . '/' . $teamId . '/messages';
        $this->writer->createDirectory($dir);
        $this->writer->write($dir . '/' . $id . '.json', JsonHelper::encode($record, JSON_PRETTY_PRINT), false);

        return $record;
    }

    /**
     * @return list<string>
     */
    /**
     * @return list<array<string, mixed>>
     */
    private function listMessages(string $teamId): array
    {
        $items = $this->listRetainedMessages($teamId);
        $window = $this->liveWindowMessages();

        return array_slice($items, -$window);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listRetainedMessages(string $teamId): array
    {
        $this->prune($teamId);
        $items = [];
        foreach ($this->messageFiles($teamId) as $name) {
            $decoded = $this->readMessage($teamId, $name);
            if ($decoded === null) {
                continue;
            }
            if (!$this->isWithinRetention((int) ($decoded['createdAt'] ?? 0))) {
                continue;
            }
            $items[] = $decoded;
        }
        usort($items, static fn (array $a, array $b): int => ((int) $a['createdAt']) <=> ((int) $b['createdAt']));

        return $items;
    }

    private function retentionDays(): int
    {
        $value = (int) ($this->settings->group('teamChat')['retentionDays'] ?? 30);

        return max(1, min(365, $value));
    }

    private function maxStoredMessages(): int
    {
        $value = (int) ($this->settings->group('teamChat')['maxStoredMessages'] ?? 800);

        return max(100, min(5000, $value));
    }

    private function liveWindowMessages(): int
    {
        $value = (int) ($this->settings->group('teamChat')['liveWindowMessages'] ?? 80);

        return max(20, min(500, $value));
    }

    private function searchMaxResults(): int
    {
        $value = (int) ($this->settings->group('teamChat')['searchMaxResults'] ?? 100);

        return max(10, min(500, $value));
    }

    private function isWithinRetention(int $createdAt): bool
    {
        if ($createdAt < 1) {
            return false;
        }

        return $createdAt >= time() - ($this->retentionDays() * 86400);
    }

    /**
     * @return array{unread: int, preview: string, lastMessageAt: int}
     */
    private function unreadStats(string $teamId, User $actor): array
    {
        $lastRead = $this->lastReadAt($teamId, $actor->getId());
        $unread = 0;
        $preview = '';
        $lastAt = 0;
        $newestUnreadPreview = '';
        $newestUnreadAt = 0;
        foreach ($this->listMessages($teamId) as $message) {
            $at = (int) ($message['createdAt'] ?? 0);
            $lastAt = max($lastAt, $at);
            if ($lastRead > 0 && $at <= $lastRead) {
                continue;
            }
            if (($message['authorUserId'] ?? '') === $actor->getId()) {
                continue;
            }
            $unread++;
            if ($at >= $newestUnreadAt) {
                $newestUnreadAt = $at;
                $newestUnreadPreview = $this->previewBody($message);
            }
        }
        if ($unread > 0) {
            $preview = $newestUnreadPreview;
        }

        return [
            'unread' => $unread,
            'preview' => $preview,
            'lastMessageAt' => $lastAt,
        ];
    }

    /**
     * @param array<string, mixed> $message
     */
    private function previewBody(array $message): string
    {
        $kind = (string) ($message['kind'] ?? self::KIND_TEXT);
        if ($kind === self::KIND_FILE) {
            $body = (string) ($message['body'] ?? '');
            return $body !== '' ? $body : 'file';
        }
        $body = trim((string) ($message['body'] ?? ''));
        if ($body === '') {
            return '';
        }
        if (mb_strlen($body) > self::PREVIEW_MAX) {
            return mb_substr($body, 0, self::PREVIEW_MAX - 1) . '…';
        }

        return $body;
    }

    private function lastReadAt(string $teamId, string $userId): int
    {
        $path = $this->readPath($teamId, $userId);
        if (!$this->reader->exists($path)) {
            return 0;
        }
        try {
            $data = JsonHelper::decode($this->reader->read($path));
        } catch (\Throwable) {
            return 0;
        }

        return is_int($data['lastReadAt'] ?? null) ? $data['lastReadAt'] : 0;
    }

    private function markRead(string $teamId, string $userId, int $lastReadAt): void
    {
        $userId = trim($userId);
        if ($userId === '' || $lastReadAt < 1) {
            return;
        }
        $existing = $this->lastReadAt($teamId, $userId);
        if ($lastReadAt <= $existing) {
            return;
        }
        $dir = $this->relativeDir . '/' . $teamId . '/read';
        $this->writer->createDirectory($dir);
        $this->writer->write(
            $dir . '/' . $userId . '.json',
            JsonHelper::encode([
                'schema' => self::READ_SCHEMA,
                'userId' => $userId,
                'teamId' => $teamId,
                'lastReadAt' => $lastReadAt,
            ], JSON_PRETTY_PRINT),
            false
        );
    }

    private function readPath(string $teamId, string $userId): string
    {
        return $this->relativeDir . '/' . $teamId . '/read/' . $userId . '.json';
    }

    /**
     * @return list<string>
     */
    private function messageFiles(string $teamId): array
    {
        $dir = $this->relativeDir . '/' . $teamId . '/messages';
        try {
            $names = $this->reader->listFiles($dir, '*.json');
        } catch (\Throwable) {
            return [];
        }
        $out = [];
        foreach ($names as $name) {
            $base = basename($name);
            if (str_ends_with($base, '.json')) {
                $out[] = $base;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readMessage(string $teamId, string $name): ?array
    {
        try {
            $data = JsonHelper::decode($this->reader->read($this->relativeDir . '/' . $teamId . '/messages/' . $name));
        } catch (\Throwable) {
            return null;
        }
        $id = $data['id'] ?? null;
        if (!is_string($id) || $id === '') {
            return null;
        }

        return [
            'schema' => is_string($data['schema'] ?? null) ? $data['schema'] : self::SCHEMA,
            'id' => $id,
            'teamId' => is_string($data['teamId'] ?? null) ? $data['teamId'] : $teamId,
            'authorUserId' => is_string($data['authorUserId'] ?? null) ? $data['authorUserId'] : '',
            'authorName' => is_string($data['authorName'] ?? null) ? $data['authorName'] : '',
            'kind' => is_string($data['kind'] ?? null) ? $data['kind'] : self::KIND_TEXT,
            'body' => is_string($data['body'] ?? null) ? $data['body'] : '',
            'language' => is_string($data['language'] ?? null) ? $data['language'] : '',
            'file' => is_array($data['file'] ?? null) ? $data['file'] : null,
            'createdAt' => is_numeric($data['createdAt'] ?? null) ? (int) $data['createdAt'] : 0,
        ];
    }

    private function prune(string $teamId): void
    {
        $parsed = [];
        foreach ($this->messageFiles($teamId) as $name) {
            $decoded = $this->readMessage($teamId, $name);
            if ($decoded === null) {
                continue;
            }
            $parsed[] = ['name' => $name, 'createdAt' => (int) ($decoded['createdAt'] ?? 0)];
        }
        if ($parsed === []) {
            return;
        }

        usort($parsed, static fn (array $a, array $b): int => $a['createdAt'] <=> $b['createdAt']);
        $cutoff = time() - ($this->retentionDays() * 86400);
        $drop = [];
        foreach ($parsed as $entry) {
            if ($entry['createdAt'] > 0 && $entry['createdAt'] < $cutoff) {
                $drop[$entry['name']] = true;
            }
        }
        $kept = array_values(array_filter($parsed, static fn (array $entry): bool => !isset($drop[$entry['name']])));
        $max = $this->maxStoredMessages();
        if (count($kept) > $max) {
            foreach (array_slice($kept, 0, count($kept) - $max) as $entry) {
                $drop[$entry['name']] = true;
            }
        }
        foreach (array_keys($drop) as $name) {
            try {
                $this->writer->delete($this->relativeDir . '/' . $teamId . '/messages/' . $name, false);
            } catch (\Throwable) {
            }
        }
    }
}
