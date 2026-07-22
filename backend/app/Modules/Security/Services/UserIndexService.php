<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file index používateľov pre O(1) lookup podľa e-mailu, ID, username a reset tokenu.
 *
 * Úložisko: `data/index/users.json` – atomický zápis cez flock(LOCK_EX).
 */
final class UserIndexService
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private string $indexFile = 'data/index/users.json'
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->indexFile, '/');
    }

    public function ensureBuilt(string $usersStoragePath): void
    {
        if (!$this->reader->exists($this->indexFile)) {
            $this->rebuild($usersStoragePath);

            return;
        }

        if ($this->readIndexFromDisk()['by_id'] !== []) {
            return;
        }

        if ($this->countUserFiles($usersStoragePath) > 0) {
            $this->rebuild($usersStoragePath);
        }
    }

    public function rebuild(string $usersStoragePath): void
    {
        $byId = [];
        $byEmail = [];
        $byUsername = [];

        foreach ($this->listUserFilenames($usersStoragePath) as $filename) {
            $entry = $this->readEntryFromUserFile($usersStoragePath, $filename);
            if ($entry === null) {
                continue;
            }

            $id = $entry['id'];
            $byId[$id] = $entry;
            $byEmail[$entry['email']] = $id;
            $byUsername[$entry['username']] = $id;
        }

        $this->writeIndex([
            'version' => 1,
            'updated_at' => date('c'),
            'by_id' => $byId,
            'by_email' => $byEmail,
            'by_username' => $byUsername,
        ]);
    }

    public function resolveIdByEmail(string $email): ?string
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }

        $index = $this->readIndexFromDisk();
        $id = $index['by_email'][$normalized] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function resolveIdByUsername(string $username): ?string
    {
        $normalized = strtolower(trim($username));
        if ($normalized === '') {
            return null;
        }

        $index = $this->readIndexFromDisk();
        $id = $index['by_username'][$normalized] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function resolveIdByResetTokenHash(string $tokenHash, int $now): ?string
    {
        if ($tokenHash === '') {
            return null;
        }

        foreach ($this->readIndexFromDisk()['by_id'] as $id => $entry) {
            $storedHash = isset($entry['resetTokenHash']) ? (string) $entry['resetTokenHash'] : '';
            if ($storedHash === '' || !hash_equals($storedHash, $tokenHash)) {
                continue;
            }

            $expires = isset($entry['resetTokenExpires']) ? (int) $entry['resetTokenExpires'] : 0;
            if ($expires > $now) {
                return $id;
            }
        }

        return null;
    }

    public function hasId(string $id): bool
    {
        return isset($this->readIndexFromDisk()['by_id'][$id]);
    }

    /**
     * @return list<string>
     */
    public function listIds(): array
    {
        return array_keys($this->readIndexFromDisk()['by_id']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsertFromRaw(array $data): void
    {
        if (!isset($data['id'], $data['email']) || !is_string($data['id']) || !is_string($data['email'])) {
            return;
        }

        if ($data['id'] === '' || trim($data['email']) === '') {
            return;
        }

        $entry = [
            'email' => strtolower(trim($data['email'])),
            'username' => strtolower(trim((string) ($data['username'] ?? ''))),
            'resetTokenHash' => isset($data['resetTokenHash']) ? (string) $data['resetTokenHash'] : null,
            'resetTokenExpires' => isset($data['resetTokenExpires']) ? (int) $data['resetTokenExpires'] : null,
        ];

        $this->withLockedIndex(function (array &$stored) use ($data, $entry): void {
            $id = $data['id'];
            $previous = $stored['by_id'][$id] ?? null;

            if (is_array($previous)) {
                $previousEmail = (string) ($previous['email'] ?? '');
                if ($previousEmail !== '' && $previousEmail !== $entry['email']) {
                    unset($stored['by_email'][$previousEmail]);
                }

                $previousUsername = (string) ($previous['username'] ?? '');
                if ($previousUsername !== '' && $previousUsername !== $entry['username']) {
                    unset($stored['by_username'][$previousUsername]);
                }
            }

            $stored['by_id'][$id] = $entry;
            $stored['by_email'][$entry['email']] = $id;

            if ($entry['username'] !== '') {
                $stored['by_username'][$entry['username']] = $id;
            }

            $stored['updated_at'] = date('c');
        });
    }

    public function remove(string $id): void
    {
        $this->withLockedIndex(function (array &$stored) use ($id): void {
            $entry = $stored['by_id'][$id] ?? null;
            if (!is_array($entry)) {
                return;
            }

            $email = (string) ($entry['email'] ?? '');
            if ($email !== '') {
                unset($stored['by_email'][$email]);
            }

            $username = (string) ($entry['username'] ?? '');
            if ($username !== '') {
                unset($stored['by_username'][$username]);
            }

            unset($stored['by_id'][$id]);
            $stored['updated_at'] = date('c');
        });
    }

    /**
     * @return list<string>
     */
    private function listUserFilenames(string $usersStoragePath): array
    {
        try {
            $files = $this->reader->listFiles($usersStoragePath, '*.json');
            $files = array_filter(
                $files,
                static fn (string $file): bool => str_ends_with($file, '.json')
                    && !str_contains($file, '.backup.')
            );

            return array_values(array_map(static fn (string $file): string => basename($file), $files));
        } catch (FlatFileException) {
            return [];
        }
    }

    private function countUserFiles(string $usersStoragePath): int
    {
        return count($this->listUserFilenames($usersStoragePath));
    }

    /**
     * @return array{id: string, email: string, username: string, resetTokenHash: ?string, resetTokenExpires: ?int}|null
     */
    private function readEntryFromUserFile(string $usersStoragePath, string $filename): ?array
    {
        try {
            $content = $this->reader->read($usersStoragePath . '/' . $filename);
            $data = json_decode($content, true);
            if (!is_array($data) || !isset($data['id'], $data['email'])) {
                return null;
            }

            if (!is_string($data['id']) || !is_string($data['email']) || $data['id'] === '' || trim($data['email']) === '') {
                return null;
            }

            return [
                'id' => $data['id'],
                'email' => strtolower(trim($data['email'])),
                'username' => strtolower(trim((string) ($data['username'] ?? ''))),
                'resetTokenHash' => isset($data['resetTokenHash']) ? (string) $data['resetTokenHash'] : null,
                'resetTokenExpires' => isset($data['resetTokenExpires']) ? (int) $data['resetTokenExpires'] : null,
            ];
        } catch (FlatFileException) {
            return null;
        }
    }

    /**
     * @return array{
     *     version: int,
     *     updated_at: string,
     *     by_id: array<string, array<string, mixed>>,
     *     by_email: array<string, string>,
     *     by_username: array<string, string>
     * }
     */
    private function readIndexFromDisk(): array
    {
        if (!is_readable($this->absolutePath)) {
            return $this->emptyIndex();
        }

        $raw = file_get_contents($this->absolutePath);
        if ($raw === false || trim($raw) === '') {
            return $this->emptyIndex();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $this->emptyIndex();
        }

        return [
            'version' => (int) ($decoded['version'] ?? 1),
            'updated_at' => (string) ($decoded['updated_at'] ?? ''),
            'by_id' => is_array($decoded['by_id'] ?? null) ? $decoded['by_id'] : [],
            'by_email' => is_array($decoded['by_email'] ?? null) ? $decoded['by_email'] : [],
            'by_username' => is_array($decoded['by_username'] ?? null) ? $decoded['by_username'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $index
     */
    private function writeIndex(array $index): void
    {
        $this->ensureStorage();
        file_put_contents(
            $this->absolutePath,
            JsonHelper::encode($index, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @template T
     * @param callable(array<string, mixed>): T $callback
     * @return T
     */
    private function withLockedIndex(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť user index: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok user indexu.');
            }

            $stored = $this->readStoredIndex($handle);
            $before = JsonHelper::encode($stored);
            $result = $callback($stored);
            $after = JsonHelper::encode($stored);

            if ($after !== $before) {
                $this->writeStoredIndex($handle, $stored);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readStoredIndex(mixed $handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return $this->emptyIndex();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $this->emptyIndex();
        }

        return [
            'version' => (int) ($decoded['version'] ?? 1),
            'updated_at' => (string) ($decoded['updated_at'] ?? ''),
            'by_id' => is_array($decoded['by_id'] ?? null) ? $decoded['by_id'] : [],
            'by_email' => is_array($decoded['by_email'] ?? null) ? $decoded['by_email'] : [],
            'by_username' => is_array($decoded['by_username'] ?? null) ? $decoded['by_username'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $stored
     */
    private function writeStoredIndex(mixed $handle, array $stored): void
    {
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, JsonHelper::encode($stored, JSON_PRETTY_PRINT));
        fflush($handle);
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->absolutePath)) {
            file_put_contents(
                $this->absolutePath,
                JsonHelper::encode($this->emptyIndex(), JSON_PRETTY_PRINT)
            );
        }
    }

    /**
     * @return array{
     *     version: int,
     *     updated_at: string,
     *     by_id: array<string, array<string, mixed>>,
     *     by_email: array<string, string>,
     *     by_username: array<string, string>
     * }
     */
    private function emptyIndex(): array
    {
        return [
            'version' => 1,
            'updated_at' => '',
            'by_id' => [],
            'by_email' => [],
            'by_username' => [],
        ];
    }
}
