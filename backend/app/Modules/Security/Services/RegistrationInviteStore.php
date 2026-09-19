<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;

/**
 * One-time registration invites at data/registration-invites/{id}.json (It.93o-8).
 * Token is stored as SHA-256 only.
 */
final class RegistrationInviteStore
{
    public const SCHEMA = 'registration-invite@1';

    public const MAX_INVITES = 200;

    public const TTL_SECONDS = 604800;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativeDir = 'data/registration-invites',
    ) {
    }

    /**
     * @return array{id: string, token: string, record: array<string, mixed>}
     */
    public function create(string $email, string $teamId, string $createdBy, string $source): array
    {
        if (count($this->list()) >= self::MAX_INVITES) {
            throw new InvalidArgumentException('Too many registration invites.');
        }

        $email = $this->normalizeEmail($email);
        $id = 'inv_' . bin2hex(random_bytes(6));
        $token = bin2hex(random_bytes(32));
        $now = time();
        $record = [
            'schema' => self::SCHEMA,
            'id' => $id,
            'email' => $email,
            'tokenHash' => $this->hashToken($token),
            'teamId' => $this->normalizeTeamId($teamId),
            'source' => $source === 'contact' ? 'contact' : 'admin',
            'createdBy' => LogSanitizer::value($createdBy, 80),
            'createdAt' => $now,
            'expiresAt' => $now + self::TTL_SECONDS,
            'usedAt' => 0,
            'userId' => '',
        ];
        $this->writeRecord($record);

        return ['id' => $id, 'token' => $token, 'record' => $this->present($record)];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $items = [];
        foreach ($this->inviteFiles() as $file) {
            $id = basename($file, '.json');
            $record = $this->readNormalized($id);
            if ($record !== null) {
                $items[] = $this->present($record);
            }
        }
        usort(
            $items,
            static fn (array $a, array $b): int => ((int) ($b['createdAt'] ?? 0)) <=> ((int) ($a['createdAt'] ?? 0))
        );

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        $record = $this->readNormalized($id);
        if ($record === null) {
            return null;
        }

        return $this->present($record);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findUsableByToken(string $token): ?array
    {
        $hash = $this->hashToken($token);
        foreach ($this->inviteFiles() as $file) {
            $record = $this->readNormalized(basename($file, '.json'));
            if ($record === null) {
                continue;
            }
            $stored = (string) ($record['tokenHash'] ?? '');
            if ($stored === '' || !hash_equals($stored, $hash)) {
                continue;
            }
            if ($this->isUsable($record)) {
                return $record;
            }

            return null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function consume(string $token, string $email, string $userId): array
    {
        $record = $this->findUsableByToken($token);
        if ($record === null) {
            throw new InvalidArgumentException('Invite is invalid or expired.');
        }
        if (!$this->emailsMatch((string) $record['email'], $email)) {
            throw new InvalidArgumentException('Invite is invalid or expired.');
        }
        $record['usedAt'] = time();
        $record['userId'] = LogSanitizer::value($userId, 80);
        $this->writeRecord($record);

        return $this->present($record);
    }

    public function revoke(string $id): void
    {
        $id = $this->normalizeId($id);
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new InvalidArgumentException('Invite not found');
        }
        $this->writer->delete($relativePath, false);
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function present(array $record): array
    {
        return [
            'id' => (string) ($record['id'] ?? ''),
            'email' => (string) ($record['email'] ?? ''),
            'teamId' => (string) ($record['teamId'] ?? ''),
            'source' => (string) ($record['source'] ?? 'admin'),
            'createdBy' => (string) ($record['createdBy'] ?? ''),
            'createdAt' => (int) ($record['createdAt'] ?? 0),
            'expiresAt' => (int) ($record['expiresAt'] ?? 0),
            'usedAt' => (int) ($record['usedAt'] ?? 0),
            'userId' => (string) ($record['userId'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $record
     */
    public function isUsable(array $record): bool
    {
        if ((int) ($record['usedAt'] ?? 0) > 0) {
            return false;
        }

        return (int) ($record['expiresAt'] ?? 0) >= time();
    }

    public function emailsMatch(string $stored, string $incoming): bool
    {
        return hash_equals($this->normalizeEmail($stored), $this->normalizeEmail($incoming));
    }

    /**
     * @param array<string, mixed> $record
     */
    private function writeRecord(array $record): void
    {
        $id = $this->normalizeId((string) ($record['id'] ?? ''));
        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->relativePath($id),
            JsonHelper::encode([
                'schema' => self::SCHEMA,
                'id' => $id,
                'email' => $this->normalizeEmail((string) ($record['email'] ?? '')),
                'tokenHash' => (string) ($record['tokenHash'] ?? ''),
                'teamId' => $this->normalizeTeamId((string) ($record['teamId'] ?? '')),
                'source' => ((string) ($record['source'] ?? 'admin')) === 'contact' ? 'contact' : 'admin',
                'createdBy' => LogSanitizer::value((string) ($record['createdBy'] ?? ''), 80),
                'createdAt' => is_int($record['createdAt'] ?? null) ? $record['createdAt'] : time(),
                'expiresAt' => is_int($record['expiresAt'] ?? null) ? $record['expiresAt'] : time() + self::TTL_SECONDS,
                'usedAt' => is_int($record['usedAt'] ?? null) ? $record['usedAt'] : 0,
                'userId' => LogSanitizer::value((string) ($record['userId'] ?? ''), 80),
            ], JSON_PRETTY_PRINT),
            false
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readNormalized(string $id): ?array
    {
        try {
            $id = $this->normalizeId($id);
        } catch (InvalidArgumentException) {
            return null;
        }
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            return null;
        }
        try {
            $data = JsonHelper::decode($this->reader->read($relativePath));
        } catch (\Throwable) {
            return null;
        }
        if ((string) ($data['id'] ?? '') !== $id) {
            return null;
        }

        return [
            'schema' => self::SCHEMA,
            'id' => $id,
            'email' => (string) ($data['email'] ?? ''),
            'tokenHash' => (string) ($data['tokenHash'] ?? ''),
            'teamId' => (string) ($data['teamId'] ?? ''),
            'source' => ((string) ($data['source'] ?? 'admin')) === 'contact' ? 'contact' : 'admin',
            'createdBy' => (string) ($data['createdBy'] ?? ''),
            'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
            'expiresAt' => is_int($data['expiresAt'] ?? null) ? $data['expiresAt'] : 0,
            'usedAt' => is_int($data['usedAt'] ?? null) ? $data['usedAt'] : 0,
            'userId' => (string) ($data['userId'] ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    private function inviteFiles(): array
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

    private function hashToken(string $token): string
    {
        $token = trim($token);
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return hash('sha256', 'invalid');
        }

        return hash('sha256', $token);
    }

    private function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('A valid e-mail is required.');
        }

        return LogSanitizer::value($email, 255);
    }

    private function normalizeTeamId(string $teamId): string
    {
        $teamId = strtolower(trim($teamId));
        if ($teamId === '') {
            return '';
        }
        if (!preg_match('/^team_[a-f0-9]{10}$/', $teamId)) {
            throw new InvalidArgumentException('Invalid team id.');
        }

        return $teamId;
    }

    private function normalizeId(string $id): string
    {
        $id = strtolower(trim($id));
        if ($id === '' || !preg_match('/^inv_[a-f0-9]{12}$/', $id)) {
            throw new InvalidArgumentException('Invalid invite id.');
        }

        return $id;
    }

    private function relativePath(string $id): string
    {
        return $this->relativeDir . '/' . $id . '.json';
    }
}
