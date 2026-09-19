<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Teams\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Discord-style room per external team. Files are download-only (It.93o-7).
 */
final class TeamChatStore
{
    public const SCHEMA = 'team-chat-message@1';

    public const KIND_TEXT = 'text';

    public const KIND_CODE = 'code';

    public const KIND_FILE = 'file';

    public const MAX_BODY = 20000;

    public const MAX_MESSAGES = 400;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private TeamRepository $teams,
        private UploadPolicyEngine $uploads,
        private string $relativeDir = 'data/team-chat',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function roomsFor(User $actor): array
    {
        $rooms = [];
        foreach ($this->teams->list(TeamRepository::TYPE_EXTERNAL) as $team) {
            if (!$this->canAccess($team, $actor)) {
                continue;
            }
            $rooms[] = [
                'id' => $team['id'],
                'name' => $team['name'],
            ];
        }

        return $rooms;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function messages(string $teamId, User $actor): array
    {
        $team = $this->requireAccess($teamId, $actor);
        $items = [];
        foreach ($this->messageFiles((string) $team['id']) as $name) {
            $decoded = $this->readMessage((string) $team['id'], $name);
            if ($decoded !== null) {
                $items[] = $decoded;
            }
        }
        usort($items, static fn (array $a, array $b): int => ((int) $a['createdAt']) <=> ((int) $b['createdAt']));

        return array_slice($items, -80);
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
        if (($team['type'] ?? '') !== TeamRepository::TYPE_EXTERNAL) {
            return false;
        }
        if ($actor->isAdmin()) {
            return true;
        }
        $members = is_array($team['memberUserIds'] ?? null) ? $team['memberUserIds'] : [];

        return in_array($actor->getId(), $members, true);
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
            if (str_ends_with($name, '.json')) {
                $out[] = $name;
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
            'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
        ];
    }

    private function prune(string $teamId): void
    {
        $files = $this->messageFiles($teamId);
        if (count($files) < self::MAX_MESSAGES) {
            return;
        }
        sort($files);
        $drop = array_slice($files, 0, max(0, count($files) - self::MAX_MESSAGES + 1));
        foreach ($drop as $name) {
            try {
                $this->writer->delete($this->relativeDir . '/' . $teamId . '/messages/' . $name, false);
            } catch (\Throwable) {
            }
        }
    }
}
