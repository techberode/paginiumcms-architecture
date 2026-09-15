<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * In-memory IMAP stand-in for tests and local demos (It.93m).
 */
final class FakeImapClient implements ImapClientInterface
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $folders = [];

    private bool $connected = false;

    public function __construct()
    {
        $this->folders = [
            'INBOX' => [
                [
                    'uid' => 1,
                    'subject' => 'Welcome',
                    'from' => 'noreply@example.com',
                    'date' => '2026-09-15',
                    'flags' => [],
                    'tags' => ['intro'],
                    'seen' => false,
                    'flagged' => false,
                    'snippet' => 'Thanks for hosting with us.',
                    'body' => 'Thanks for hosting with us.',
                ],
            ],
            'Junk' => [],
        ];
    }

    public function connect(string $host, int $port, string $encryption, string $username, string $password): void
    {
        unset($host, $port, $encryption);
        if ($username === '' || $password === '') {
            throw new InvalidArgumentException('Mailbox credentials are required.');
        }
        $this->connected = true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    public function folders(): array
    {
        $this->assertConnected();
        $out = [];
        foreach (array_keys($this->folders) as $name) {
            $out[] = ['name' => $name, 'spam' => SiteMailboxGuard::isSpamFolder($name)];
        }

        return $out;
    }

    public function createFolder(string $name): void
    {
        $this->assertConnected();
        $name = trim($name);
        if ($name === '' || strlen($name) > 40) {
            throw new InvalidArgumentException('Folder name is invalid.');
        }
        if (!isset($this->folders[$name])) {
            $this->folders[$name] = [];
        }
    }

    public function deleteFolder(string $name): void
    {
        $this->assertConnected();
        $name = trim($name);
        if ($name === '' || strcasecmp($name, 'INBOX') === 0) {
            throw new InvalidArgumentException('Folder cannot be deleted.');
        }
        unset($this->folders[$name]);
    }

    public function messages(string $folder, int $limit = 40): array
    {
        $this->assertConnected();
        $rows = $this->folders[$folder] ?? [];
        $out = [];
        foreach (array_slice($rows, 0, max(1, $limit)) as $row) {
            $out[] = $this->overview($row);
        }

        return $out;
    }

    public function message(string $folder, int $uid): array
    {
        $this->assertConnected();
        foreach ($this->folders[$folder] ?? [] as $row) {
            if ((int) $row['uid'] === $uid) {
                return $row;
            }
        }

        throw new InvalidArgumentException('Message not found');
    }

    public function setTags(string $folder, int $uid, array $tags): void
    {
        $this->assertConnected();
        $clean = [];
        foreach ($tags as $tag) {
            if (preg_match('/^[a-zA-Z0-9_-]{1,24}$/', $tag) === 1) {
                $clean[] = strtolower($tag);
            }
        }
        $this->patchMessage($folder, $uid, ['tags' => array_values(array_unique($clean))]);
    }

    public function addFlags(string $folder, int $uid, array $flags): void
    {
        $this->assertConnected();
        $this->applyFlagDelta($folder, $uid, $flags, true);
    }

    public function removeFlags(string $folder, int $uid, array $flags): void
    {
        $this->assertConnected();
        $this->applyFlagDelta($folder, $uid, $flags, false);
    }

    public function move(string $folder, int $uid, string $target): void
    {
        $this->assertConnected();
        if (!isset($this->folders[$target])) {
            $this->folders[$target] = [];
        }
        $kept = [];
        $moved = null;
        foreach ($this->folders[$folder] ?? [] as $row) {
            if ((int) $row['uid'] === $uid) {
                $moved = $row;
                continue;
            }
            $kept[] = $row;
        }
        if ($moved === null) {
            throw new InvalidArgumentException('Message not found');
        }
        $this->folders[$folder] = $kept;
        $this->folders[$target][] = $moved;
    }

    /**
     * @param list<string> $flags
     */
    private function applyFlagDelta(string $folder, int $uid, array $flags, bool $add): void
    {
        $row = $this->findMessage($folder, $uid);
        $tags = [];
        foreach ($row['tags'] ?? [] as $existing) {
            if (is_string($existing)) {
                $tags[] = $existing;
            }
        }
        $seen = (bool) ($row['seen'] ?? false);
        $flagged = (bool) ($row['flagged'] ?? false);
        foreach ($flags as $flag) {
            $token = strtolower(trim($flag, '\\$ '));
            if ($token === 'seen') {
                $seen = $add;
            } elseif ($token === 'flagged') {
                $flagged = $add;
            } elseif (preg_match('/^[a-z0-9_-]{1,24}$/', $token) === 1) {
                if ($add) {
                    $tags[] = $token;
                } else {
                    $tags = array_values(array_filter($tags, static fn (string $tag): bool => $tag !== $token));
                }
            }
        }
        $this->patchMessage($folder, $uid, [
            'tags' => array_values(array_unique($tags)),
            'seen' => $seen,
            'flagged' => $flagged,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function findMessage(string $folder, int $uid): array
    {
        foreach ($this->folders[$folder] ?? [] as $row) {
            if ((int) $row['uid'] === $uid) {
                return $row;
            }
        }

        throw new InvalidArgumentException('Message not found');
    }

    /**
     * @param array<string, mixed> $patch
     */
    private function patchMessage(string $folder, int $uid, array $patch): void
    {
        foreach ($this->folders[$folder] ?? [] as $index => $row) {
            if ((int) $row['uid'] === $uid) {
                $this->folders[$folder][$index] = array_merge($row, $patch);

                return;
            }
        }

        throw new InvalidArgumentException('Message not found');
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function overview(array $row): array
    {
        unset($row['body']);

        return $row;
    }

    private function assertConnected(): void
    {
        if (!$this->connected) {
            throw new RuntimeException('IMAP is not connected.');
        }
    }
}
