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

    public const LOCAL_BUCKET_SENT = 'sent';

    public const LOCAL_BUCKET_DRAFT = 'draft';

    private const LOCAL_MESSAGES_MAX = 5000;

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
        $this->writeBucket($userId, $mailbox, ['hiddenMessages' => $rows]);
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
        $this->writeBucket($userId, $mailbox, ['hiddenMessages' => $kept]);
    }

    /**
     * Move every locally hidden message to the permanent client dismiss list and clear local trash.
     */
    public function purgeHiddenMessages(string $userId, string $mailbox = ''): int
    {
        $hidden = $this->hiddenMessages($userId, $mailbox);
        if ($hidden === []) {
            return 0;
        }
        $merged = $this->mergePurgedClientMessages($this->purgedClientMessages($userId, $mailbox), $hidden);
        $this->writeBucket($userId, $mailbox, [
            'hiddenMessages' => [],
            'purgedClientMessages' => $merged,
        ]);

        return count($hidden);
    }

    /**
     * @return list<array{folder: string, uid: int}>
     */
    public function purgedClientMessages(string $userId, string $mailbox = ''): array
    {
        $out = [];
        foreach ($this->bucket($userId, $mailbox)['purgedClientMessages'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $folder = is_string($row['folder'] ?? null) ? $row['folder'] : '';
            $uid = (int) ($row['uid'] ?? 0);
            if ($folder === '' || $uid < 1) {
                continue;
            }
            $out[] = ['folder' => $folder, 'uid' => $uid];
        }

        return $out;
    }

    public function isClientMessagePurged(string $userId, string $folder, int $uid, string $mailbox = ''): bool
    {
        if ($uid < 1) {
            return false;
        }
        foreach ($this->purgedClientMessages($userId, $mailbox) as $row) {
            if ($row['folder'] === $folder && $row['uid'] === $uid) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{folder: string, uid: int}> $existing
     * @param list<array{folder: string, uid: int, subject?: string, from?: string, date?: string}> $hiddenRows
     * @return list<array{folder: string, uid: int}>
     */
    private function mergePurgedClientMessages(array $existing, array $hiddenRows): array
    {
        $seen = [];
        $out = [];
        foreach (array_merge($existing, $hiddenRows) as $row) {
            $folder = $row['folder'];
            $uid = $row['uid'];
            $key = $folder . "\0" . $uid;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['folder' => $folder, 'uid' => $uid];
        }
        if (count($out) > 3000) {
            $out = array_slice($out, -3000);
        }

        return $out;
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
        $this->writeBucket($userId, $mailbox, ['hiddenFolders' => $folders]);
    }

    /**
     * @return list<string>
     */
    public function blockedSenders(string $userId, string $mailbox = ''): array
    {
        $out = [];
        foreach ($this->bucket($userId, $mailbox)['blockedSenders'] as $email) {
            if (is_string($email) && MailFromAddress::isPlausibleEmail($email)) {
                $out[] = $email;
            }
        }

        return array_values(array_unique($out));
    }

    public function blockSender(string $userId, string $fromHeader, string $mailbox = ''): string
    {
        $email = MailFromAddress::parse($fromHeader);
        if (!MailFromAddress::isPlausibleEmail($email)) {
            throw new InvalidArgumentException('Sender address is invalid.');
        }
        $list = $this->blockedSenders($userId, $mailbox);
        if (!in_array($email, $list, true)) {
            $list[] = $email;
        }
        if (count($list) > 200) {
            $list = array_slice($list, -200);
        }
        $this->writeBucket($userId, $mailbox, ['blockedSenders' => $list]);

        return $email;
    }

    public function isSenderBlocked(string $userId, string $fromHeader, string $mailbox = ''): bool
    {
        $email = MailFromAddress::parse($fromHeader);

        return in_array($email, $this->blockedSenders($userId, $mailbox), true);
    }

    public function unblockSender(string $userId, string $emailOrHeader, string $mailbox = ''): bool
    {
        $email = MailFromAddress::parse($emailOrHeader);
        if (!MailFromAddress::isPlausibleEmail($email)) {
            throw new InvalidArgumentException('Sender address is invalid.');
        }
        $list = $this->blockedSenders($userId, $mailbox);
        $next = array_values(array_filter($list, static fn (string $item): bool => $item !== $email));
        if (count($next) === count($list)) {
            return false;
        }
        $this->writeBucket($userId, $mailbox, ['blockedSenders' => $next]);

        return true;
    }

    /**
     * @return list<int>
     */
    public function purgedSpamUids(string $userId, string $mailbox = ''): array
    {
        $out = [];
        foreach ($this->bucket($userId, $mailbox)['purgedSpamUids'] as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $out[] = $uid;
            }
        }

        return array_values(array_unique($out));
    }

    public function isSpamPurged(string $userId, int $uid, string $mailbox = ''): bool
    {
        return in_array($uid, $this->purgedSpamUids($userId, $mailbox), true);
    }

    /**
     * @return array{enabled: bool, templateId: string, overrides: array<string, string>}
     */
    public function signaturePrefs(string $userId, string $mailbox = ''): array
    {
        return $this->normalizeSignature($this->bucket($userId, $mailbox)['signature'] ?? []);
    }

    /**
     * @param array{enabled?: bool, templateId?: string, overrides?: array<string, mixed>} $prefs
     */
    public function saveSignaturePrefs(string $userId, array $prefs, string $mailbox = ''): void
    {
        $current = $this->signaturePrefs($userId, $mailbox);
        $next = $this->normalizeSignature(array_merge($current, $prefs));
        $this->writeBucket($userId, $mailbox, ['signature' => $next]);
    }

    public function clearSignatureOverrides(string $userId, string $mailbox = ''): void
    {
        $current = $this->signaturePrefs($userId, $mailbox);
        $current['overrides'] = [];
        $this->writeBucket($userId, $mailbox, ['signature' => $current]);
    }

    /**
     * @param list<int> $uids
     */
    public function addPurgedSpamUids(string $userId, array $uids, string $mailbox = ''): int
    {
        $merged = array_values(array_unique(array_merge($this->purgedSpamUids($userId, $mailbox), $uids)));
        if (count($merged) > 3000) {
            $merged = array_slice($merged, -3000);
        }
        $this->writeBucket($userId, $mailbox, ['purgedSpamUids' => $merged]);

        return count($uids);
    }

    public function delete(string $userId): void
    {
        $path = $this->path($userId);
        if ($this->reader->exists($path)) {
            $this->writer->delete($path, false);
        }
    }

    /**
     * @param array{to: string, from: string, subject: string, body: string, html: string, date?: string, tags?: list<string>} $payload
     */
    public function addLocalSent(string $userId, array $payload, string $mailbox = ''): int
    {
        return $this->appendLocalMessage($userId, self::LOCAL_BUCKET_SENT, $payload, $mailbox);
    }

    /**
     * @param array{to?: string, from?: string, subject?: string, body?: string, html?: string, date?: string, tags?: list<string>} $payload
     */
    public function saveLocalDraft(string $userId, array $payload, string $mailbox = '', int $uid = 0): int
    {
        if ($uid < 0) {
            $existing = $this->findLocalMessage($userId, $uid, $mailbox);
            if ($existing !== null && ($existing['bucket'] ?? '') === self::LOCAL_BUCKET_DRAFT) {
                $merged = $existing;
                foreach (['to', 'from', 'subject', 'body', 'html', 'date', 'tags'] as $key) {
                    if (array_key_exists($key, $payload)) {
                        $merged[$key] = $payload[$key];
                    }
                }
                $this->replaceLocalMessage($userId, $uid, $merged, $mailbox);

                return $uid;
            }
        }

        return $this->appendLocalMessage($userId, self::LOCAL_BUCKET_DRAFT, [
            'to' => (string) ($payload['to'] ?? ''),
            'from' => (string) ($payload['from'] ?? ''),
            'subject' => (string) ($payload['subject'] ?? ''),
            'body' => (string) ($payload['body'] ?? ''),
            'html' => (string) ($payload['html'] ?? ''),
            'date' => (string) ($payload['date'] ?? gmdate('D, d M Y H:i:s O')),
            'tags' => is_array($payload['tags'] ?? null) ? $payload['tags'] : [],
        ], $mailbox);
    }

    public function deleteLocalMessage(string $userId, int $uid, string $mailbox = ''): bool
    {
        if ($uid >= 0) {
            throw new InvalidArgumentException('Only client-local messages can be deleted this way.');
        }
        $kept = [];
        $removed = false;
        foreach ($this->localMessages($userId, $mailbox) as $row) {
            if ((int) ($row['uid'] ?? 0) === $uid) {
                $removed = true;
                continue;
            }
            $kept[] = $row;
        }
        if (!$removed) {
            return false;
        }
        $this->writeBucket($userId, $mailbox, ['localMessages' => $kept]);

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function localMessages(string $userId, string $mailbox = '', ?string $bucket = null): array
    {
        $rows = [];
        foreach ($this->bucket($userId, $mailbox)['localMessages'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = $this->normalizeLocalMessage($row);
            if ($normalized === null) {
                continue;
            }
            if ($bucket !== null && $normalized['bucket'] !== $bucket) {
                continue;
            }
            $rows[] = $normalized;
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) $b['date'], (string) $a['date']);
        });

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLocalMessage(string $userId, int $uid, string $mailbox = ''): ?array
    {
        if ($uid >= 0) {
            return null;
        }
        foreach ($this->localMessages($userId, $mailbox) as $row) {
            if ((int) $row['uid'] === $uid) {
                return $row;
            }
        }

        return null;
    }

    public static function isLocalUid(int $uid): bool
    {
        return $uid < 0;
    }

    /**
     * @param array{to: string, from: string, subject: string, body: string, html: string, date?: string, tags?: list<string>} $payload
     */
    private function appendLocalMessage(string $userId, string $bucket, array $payload, string $mailbox): int
    {
        $rows = $this->localMessages($userId, $mailbox);
        $uid = -1;
        foreach ($rows as $row) {
            $existing = (int) ($row['uid'] ?? 0);
            if ($existing <= $uid) {
                $uid = $existing - 1;
            }
        }
        $normalized = $this->normalizeLocalMessage([
            'uid' => $uid,
            'bucket' => $bucket,
            'to' => $payload['to'],
            'from' => $payload['from'],
            'subject' => $payload['subject'],
            'body' => $payload['body'],
            'html' => $payload['html'],
            'date' => $payload['date'] ?? gmdate('D, d M Y H:i:s O'),
            'tags' => $payload['tags'] ?? [],
        ]);
        if ($normalized === null) {
            throw new InvalidArgumentException('Local message payload is invalid.');
        }
        $rows[] = $normalized;
        if (count($rows) > self::LOCAL_MESSAGES_MAX) {
            $rows = array_slice($rows, -self::LOCAL_MESSAGES_MAX);
        }
        $this->writeBucket($userId, $mailbox, ['localMessages' => $rows]);

        return $uid;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function replaceLocalMessage(string $userId, int $uid, array $payload, string $mailbox): void
    {
        $rows = [];
        foreach ($this->localMessages($userId, $mailbox) as $row) {
            if ((int) ($row['uid'] ?? 0) === $uid) {
                $rows[] = $this->normalizeLocalMessage(array_merge($row, $payload, ['uid' => $uid]));

                continue;
            }
            $rows[] = $row;
        }
        $this->writeBucket($userId, $mailbox, ['localMessages' => $rows]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function normalizeLocalMessage(array $row): ?array
    {
        $uid = (int) ($row['uid'] ?? 0);
        $bucket = is_string($row['bucket'] ?? null) ? $row['bucket'] : '';
        if ($uid >= 0 || !in_array($bucket, [self::LOCAL_BUCKET_SENT, self::LOCAL_BUCKET_DRAFT], true)) {
            return null;
        }

        return [
            'uid' => $uid,
            'bucket' => $bucket,
            'to' => is_string($row['to'] ?? null) ? $row['to'] : '',
            'from' => is_string($row['from'] ?? null) ? $row['from'] : '',
            'subject' => is_string($row['subject'] ?? null) ? $row['subject'] : '',
            'body' => is_string($row['body'] ?? null) ? $row['body'] : '',
            'html' => is_string($row['html'] ?? null) ? $row['html'] : '',
            'date' => is_string($row['date'] ?? null) ? $row['date'] : '',
            'tags' => array_values(is_array($row['tags'] ?? null) ? $row['tags'] : []),
        ];
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
        foreach (['schema', 'hiddenMessages', 'hiddenFolders', 'blockedSenders', 'purgedSpamUids', 'purgedClientMessages', 'signature', 'localMessages', 'mailboxes'] as $key) {
            if (array_key_exists($key, $decoded)) {
                $out[$key] = $decoded[$key];
            }
        }

        return $out;
    }

    /**
     * @return array{
     *   hiddenMessages: mixed,
     *   hiddenFolders: mixed,
     *   blockedSenders: mixed,
     *   purgedSpamUids: mixed,
     *   purgedClientMessages: mixed,
     *   signature: mixed,
     *   localMessages: mixed
     * }
     */
    private function bucket(string $userId, string $mailbox): array
    {
        $data = $this->read($userId);
        $mailbox = strtolower(trim($mailbox));
        if ($mailbox === '') {
            return $this->normalizeBucket([
                'hiddenMessages' => $data['hiddenMessages'] ?? [],
                'hiddenFolders' => $data['hiddenFolders'] ?? [],
                'blockedSenders' => $data['blockedSenders'] ?? [],
                'purgedSpamUids' => $data['purgedSpamUids'] ?? [],
                'purgedClientMessages' => $data['purgedClientMessages'] ?? [],
                'signature' => $data['signature'] ?? [],
                'localMessages' => $data['localMessages'] ?? [],
            ]);
        }
        $mailboxes = is_array($data['mailboxes'] ?? null) ? $data['mailboxes'] : [];
        $bucket = is_array($mailboxes[$mailbox] ?? null) ? $mailboxes[$mailbox] : [];

        return $this->normalizeBucket($bucket);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeBucket(string $userId, string $mailbox, array $payload): void
    {
        $mailbox = strtolower(trim($mailbox));
        $data = $this->read($userId);
        if ($mailbox === '') {
            $merged = $this->normalizeBucket(array_merge($this->bucket($userId, ''), $payload));
            $this->write($userId, $merged, is_array($data['mailboxes'] ?? null) ? $data['mailboxes'] : []);

            return;
        }
        $mailboxes = is_array($data['mailboxes'] ?? null) ? $data['mailboxes'] : [];
        $mailboxes[$mailbox] = $this->normalizeBucket(array_merge($this->bucket($userId, $mailbox), $payload));
        $this->write($userId, $this->bucket($userId, ''), $mailboxes);
    }

    /**
     * @param array<string, mixed> $bucket
     * @return array{
     *   hiddenMessages: list<mixed>,
     *   hiddenFolders: list<mixed>,
     *   blockedSenders: list<mixed>,
     *   purgedSpamUids: list<mixed>,
     *   purgedClientMessages: list<mixed>,
     *   signature: array{enabled: bool, templateId: string, overrides: array<string, string>},
     *   localMessages: list<mixed>
     * }
     */
    private function normalizeBucket(array $bucket): array
    {
        return [
            'hiddenMessages' => array_values(is_array($bucket['hiddenMessages'] ?? null) ? $bucket['hiddenMessages'] : []),
            'hiddenFolders' => array_values(is_array($bucket['hiddenFolders'] ?? null) ? $bucket['hiddenFolders'] : []),
            'blockedSenders' => array_values(is_array($bucket['blockedSenders'] ?? null) ? $bucket['blockedSenders'] : []),
            'purgedSpamUids' => array_values(is_array($bucket['purgedSpamUids'] ?? null) ? $bucket['purgedSpamUids'] : []),
            'purgedClientMessages' => array_values(is_array($bucket['purgedClientMessages'] ?? null) ? $bucket['purgedClientMessages'] : []),
            'signature' => $this->normalizeSignature(is_array($bucket['signature'] ?? null) ? $bucket['signature'] : []),
            'localMessages' => array_values(is_array($bucket['localMessages'] ?? null) ? $bucket['localMessages'] : []),
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{enabled: bool, templateId: string, overrides: array<string, string>}
     */
    private function normalizeSignature(array $raw): array
    {
        $templateId = is_string($raw['templateId'] ?? null) ? $raw['templateId'] : 'classic';
        if (!MailSignatureRenderer::isValidTemplate($templateId)) {
            $templateId = 'classic';
        }
        $overrides = [];
        if (is_array($raw['overrides'] ?? null)) {
            $overrides = MailSignatureFieldMerge::normalizeOverrides($raw['overrides']);
        }

        return [
            'enabled' => filter_var($raw['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'templateId' => $templateId,
            'overrides' => $overrides,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $mailboxes
     */
    private function write(string $userId, array $payload, array $mailboxes = []): void
    {
        $normalized = $this->normalizeBucket($payload);
        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->path($userId),
            JsonHelper::encode([
                'schema' => self::SCHEMA,
                'hiddenMessages' => $normalized['hiddenMessages'],
                'hiddenFolders' => $normalized['hiddenFolders'],
                'blockedSenders' => $normalized['blockedSenders'],
                'purgedSpamUids' => $normalized['purgedSpamUids'],
                'purgedClientMessages' => $normalized['purgedClientMessages'],
                'signature' => $normalized['signature'],
                'localMessages' => $normalized['localMessages'],
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
