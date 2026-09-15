<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Local-only mail client state (It.93m). Hides messages/folders in this CMS client; IMAP stays intact.
 */
final class MailClientStateRepository
{
    public const SCHEMA = 'mail-client@1';

    public const LOCAL_TRASH = '__local_trash';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativeDir = 'data/mail-client',
    ) {
    }

    /**
     * @return list<int>
     */
    public function hiddenUids(string $userId, string $folder, string $mailbox = ''): array
    {
        $hidden = [];
        foreach ($this->hiddenMessages($userId, $mailbox) as $row) {
            if ($row['folder'] === $folder) {
                $hidden[] = $row['uid'];
            }
        }

        return array_values(array_unique($hidden));
    }

    /**
     * @return list<array{folder: string, uid: int, subject: string, from: string, date: string}>
     */
    public function hiddenMessages(string $userId, string $mailbox = ''): array
    {
        $data = $this->bucket($userId, $mailbox);
        $out = [];
        foreach ($data['hiddenMessages'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $folder = is_string($row['folder'] ?? null) ? $row['folder'] : '';
            $uid = (int) ($row['uid'] ?? 0);
            if ($folder === '' || $uid < 1) {
                continue;
            }
            $out[] = [
                'folder' => $folder,
                'uid' => $uid,
                'subject' => is_string($row['subject'] ?? null) ? $row['subject'] : '',
                'from' => is_string($row['from'] ?? null) ? $row['from'] : '',
                'date' => is_string($row['date'] ?? null) ? $row['date'] : '',
            ];
        }

        return $out;
    }

    /**
     * @param array{subject?: string, from?: string, date?: string} $meta
     */
    public function hideMessage(string $userId, string $folder, int $uid, array $meta = [], string $mailbox = ''): void
    {
        if ($uid < 1) {
            throw new InvalidArgumentException('Message uid is invalid.');
        }
        $rows = $this->hiddenMessages($userId, $mailbox);
        foreach ($rows as $row) {
            if ($row['folder'] === $folder && $row['uid'] === $uid) {
                return;
            }
        }
        $rows[] = [
            'folder' => $folder,
            'uid' => $uid,
            'subject' => (string) ($meta['subject'] ?? ''),
            'from' => (string) ($meta['from'] ?? ''),
            'date' => (string) ($meta['date'] ?? ''),
        ];
        if (count($rows) > 500) {
            $rows = array_slice($rows, -500);
        }
        $this->writeBucket($userId, $mailbox, ['hiddenMessages' => $rows, 'hiddenFolders' => $this->hiddenFolders($userId, $mailbox)]);
    }

    public function unhideMessage(string $userId, string $folder, int $uid, string $mailbox = ''): void
    {
        $kept = [];
        foreach ($this->hiddenMessages($userId, $mailbox) as $row) {
            if ($row['folder'] === $folder && $row['uid'] === $uid) {
                continue;
            }
            $kept[] = $row;
        }
        $this->writeBucket($userId, $mailbox, ['hiddenMessages' => $kept, 'hiddenFolders' => $this->hiddenFolders($userId, $mailbox)]);
    }

    /**
     * @return list<string>
     */
    public function hiddenFolders(string $userId, string $mailbox = ''): array
    {
        $data = $this->bucket($userId, $mailbox);
        $out = [];
        foreach ($data['hiddenFolders'] as $name) {
            if (is_string($name) && $name !== '') {
                $out[] = $name;
            }
        }

        return array_values(array_unique($out));
    }

    public function hideFolder(string $userId, string $name, string $mailbox = ''): void
    {
        $name = trim($name);
        if ($name === '' || strcasecmp($name, 'INBOX') === 0) {
            throw new InvalidArgumentException('Folder cannot be hidden.');
        }
        $folders = $this->hiddenFolders($userId, $mailbox);
        if (!in_array($name, $folders, true)) {
            $folders[] = $name;
        }
        $this->writeBucket($userId, $mailbox, ['hiddenMessages' => $this->hiddenMessages($userId, $mailbox), 'hiddenFolders' => $folders]);
    }

    public function delete(string $userId): void
    {
        $path = $this->path($userId);
        if ($this->reader->exists($path)) {
            $this->writer->delete($path, false);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $userId): array
    {
        $path = $this->path($userId);
        if (!$this->reader->exists($path)) {
            return [];
        }
        try {
            $decoded = JsonHelper::decode($this->reader->read($path));
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach (['schema', 'hiddenMessages', 'hiddenFolders', 'mailboxes'] as $key) {
            if (array_key_exists($key, $decoded)) {
                $out[$key] = $decoded[$key];
            }
        }

        return $out;
    }

    /**
     * @return array{hiddenMessages: mixed, hiddenFolders: mixed}
     */
    private function bucket(string $userId, string $mailbox): array
    {
        $data = $this->read($userId);
        $mailbox = strtolower(trim($mailbox));
        if ($mailbox === '') {
            return [
                'hiddenMessages' => $data['hiddenMessages'] ?? [],
                'hiddenFolders' => $data['hiddenFolders'] ?? [],
            ];
        }
        $mailboxes = is_array($data['mailboxes'] ?? null) ? $data['mailboxes'] : [];
        $bucket = is_array($mailboxes[$mailbox] ?? null) ? $mailboxes[$mailbox] : [];

        return [
            'hiddenMessages' => $bucket['hiddenMessages'] ?? [],
            'hiddenFolders' => $bucket['hiddenFolders'] ?? [],
        ];
    }

    /**
     * @param array{hiddenMessages: list<array<string, mixed>>, hiddenFolders: list<string>} $payload
     */
    private function writeBucket(string $userId, string $mailbox, array $payload): void
    {
        $mailbox = strtolower(trim($mailbox));
        $data = $this->read($userId);
        if ($mailbox === '') {
            $this->write($userId, $payload, is_array($data['mailboxes'] ?? null) ? $data['mailboxes'] : []);

            return;
        }
        $mailboxes = is_array($data['mailboxes'] ?? null) ? $data['mailboxes'] : [];
        $mailboxes[$mailbox] = [
            'hiddenMessages' => $payload['hiddenMessages'],
            'hiddenFolders' => $payload['hiddenFolders'],
        ];
        $this->write(
            $userId,
            [
                'hiddenMessages' => $this->hiddenMessages($userId),
                'hiddenFolders' => $this->hiddenFolders($userId),
            ],
            $mailboxes
        );
    }

    /**
     * @param array{hiddenMessages: list<array<string, mixed>>, hiddenFolders: list<string>} $payload
     * @param array<string, mixed> $mailboxes
     */
    private function write(string $userId, array $payload, array $mailboxes = []): void
    {
        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->path($userId),
            JsonHelper::encode([
                'schema' => self::SCHEMA,
                'hiddenMessages' => $payload['hiddenMessages'],
                'hiddenFolders' => $payload['hiddenFolders'],
                'mailboxes' => $mailboxes,
            ]),
            false
        );
    }

    private function path(string $userId): string
    {
        return $this->relativeDir . '/' . $this->normalizeUserId($userId) . '.json';
    }

    private function normalizeUserId(string $userId): string
    {
        $userId = trim($userId);
        if ($userId === '' || strlen($userId) > 128) {
            throw new InvalidArgumentException('User id is invalid.');
        }

        return 'u_' . substr(hash('sha256', $userId), 0, 32);
    }
}
