<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Support\JsonHelper;

/**
 * Per-user IMAP passwords at rest (It.93m). Never stores message bodies.
 * Schema @2 holds extra domain mailboxes; @1 single-password files still decrypt.
 */
final class MailboxSecretRepository
{
    public const SCHEMA = 'mail-secret@2';

    public const LEGACY_SCHEMA = 'mail-secret@1';

    private const MAX_ACCOUNTS = 10;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private EncryptionService $encryption,
        private string $relativeDir = 'data/mail-secrets',
    ) {
    }

    public function getPassword(string $userId, string $mailbox = ''): ?string
    {
        $doc = $this->readDocument($userId);
        $mailbox = strtolower(trim($mailbox));
        if ($mailbox !== '') {
            $cipher = $doc['accounts'][$mailbox] ?? '';
            if ($cipher === '' && $this->isPrimary($doc, $mailbox)) {
                $cipher = $doc['legacy'];
            }
        } else {
            $active = $doc['active'];
            $cipher = $active !== '' ? ($doc['accounts'][$active] ?? '') : '';
            if ($cipher === '') {
                $cipher = $doc['legacy'];
            }
        }
        if ($cipher === '') {
            return null;
        }
        $plain = $this->encryption->decryptNullable($cipher);

        return $plain !== null && $plain !== '' ? $plain : null;
    }

    public function savePassword(string $userId, string $password, string $mailbox = ''): void
    {
        $password = trim($password);
        if ($password === '') {
            $this->delete($userId);

            return;
        }

        $mailbox = strtolower(trim($mailbox));
        $doc = $this->readDocument($userId);
        if ($mailbox === '') {
            $mailbox = $doc['active'] !== '' ? $doc['active'] : $doc['primary'];
        }
        if ($mailbox === '') {
            $this->writeLegacy($userId, $password);

            return;
        }

        $accounts = $doc['accounts'];
        $accounts[$mailbox] = $this->encryption->encrypt($password);
        $primary = $doc['primary'] !== '' ? $doc['primary'] : $mailbox;
        $active = $doc['active'] !== '' ? $doc['active'] : $mailbox;
        $this->writeDocument($userId, $primary, $active, $accounts);
    }

    /**
     * @return list<array{mailbox: string, primary: bool, hasPassword: bool}>
     */
    public function listAccounts(string $userId, string $primaryMailbox): array
    {
        $primary = $this->assertMailboxKey($primaryMailbox);
        $doc = $this->readDocument($userId);
        if ($doc['primary'] === '') {
            $doc['primary'] = $primary;
        }
        $seen = [];
        $out = [];
        $ordered = array_merge([$primary], array_keys($doc['accounts']));
        foreach ($ordered as $mailbox) {
            if (isset($seen[$mailbox])) {
                continue;
            }
            $seen[$mailbox] = true;
            $cipher = $doc['accounts'][$mailbox] ?? '';
            if ($mailbox === $primary && $cipher === '') {
                $cipher = $doc['legacy'];
            }
            $out[] = [
                'mailbox' => $mailbox,
                'primary' => $mailbox === $primary,
                'hasPassword' => $cipher !== '',
            ];
        }

        return $out;
    }

    public function activeMailbox(string $userId, string $primaryMailbox): string
    {
        $primary = $this->assertMailboxKey($primaryMailbox);
        $doc = $this->readDocument($userId);
        $active = $doc['active'];
        if ($active === '' || ($active !== $primary && !isset($doc['accounts'][$active]))) {
            return $primary;
        }

        return $active;
    }

    public function setActive(string $userId, string $mailbox, string $primaryMailbox): void
    {
        $mailbox = $this->assertMailboxKey($mailbox);
        $primary = $this->assertMailboxKey($primaryMailbox);
        $doc = $this->readDocument($userId);
        if ($mailbox !== $primary && !isset($doc['accounts'][$mailbox])) {
            throw new InvalidArgumentException('Mailbox is not added.');
        }
        $accounts = $doc['accounts'];
        if ($doc['legacy'] !== '' && !isset($accounts[$primary])) {
            $accounts[$primary] = $doc['legacy'];
        }
        $this->writeDocument($userId, $primary, $mailbox, $accounts);
    }

    public function addAccount(string $userId, string $mailbox, string $password, string $primaryMailbox): void
    {
        $mailbox = $this->assertMailboxKey($mailbox);
        $primary = $this->assertMailboxKey($primaryMailbox);
        $password = trim($password);
        if ($password === '') {
            throw new InvalidArgumentException('Mailbox password is required.');
        }
        $doc = $this->readDocument($userId);
        $accounts = $doc['accounts'];
        if ($doc['legacy'] !== '' && !isset($accounts[$primary])) {
            $accounts[$primary] = $doc['legacy'];
        }
        if ($mailbox === $primary || isset($accounts[$mailbox])) {
            throw new InvalidArgumentException('Mailbox is already added.');
        }
        if (count($accounts) >= self::MAX_ACCOUNTS) {
            throw new InvalidArgumentException('Too many mailboxes.');
        }
        $accounts[$mailbox] = $this->encryption->encrypt($password);
        $this->writeDocument($userId, $primary, $mailbox, $accounts);
    }

    public function removeAccount(string $userId, string $mailbox, string $primaryMailbox): void
    {
        $mailbox = $this->assertMailboxKey($mailbox);
        $primary = $this->assertMailboxKey($primaryMailbox);
        if ($mailbox === $primary) {
            throw new InvalidArgumentException('This mailbox cannot be removed.');
        }
        $doc = $this->readDocument($userId);
        $accounts = $doc['accounts'];
        unset($accounts[$mailbox]);
        $active = $doc['active'] === $mailbox ? $primary : ($doc['active'] !== '' ? $doc['active'] : $primary);
        $this->writeDocument($userId, $primary, $active, $accounts);
    }

    public function delete(string $userId): void
    {
        $path = $this->path($userId);
        if ($this->reader->exists($path)) {
            $this->writer->delete($path, false);
        }
    }

    /**
     * @return array{primary: string, active: string, accounts: array<string, string>, legacy: string}
     */
    private function readDocument(string $userId): array
    {
        $empty = ['primary' => '', 'active' => '', 'accounts' => [], 'legacy' => ''];
        $path = $this->path($userId);
        if (!$this->reader->exists($path)) {
            return $empty;
        }

        try {
            $data = JsonHelper::decode($this->reader->read($path));
        } catch (\Throwable) {
            return $empty;
        }

        $legacy = is_string($data['password'] ?? null) ? $data['password'] : '';
        $accounts = [];
        $rawAccounts = is_array($data['accounts'] ?? null) ? $data['accounts'] : [];
        foreach ($rawAccounts as $mailbox => $cipher) {
            if (!is_string($mailbox) || !is_string($cipher) || $cipher === '') {
                continue;
            }
            try {
                $key = $this->assertMailboxKey($mailbox);
            } catch (InvalidArgumentException) {
                continue;
            }
            $accounts[$key] = $cipher;
        }
        $primary = is_string($data['primary'] ?? null) ? strtolower(trim($data['primary'])) : '';
        $active = is_string($data['active'] ?? null) ? strtolower(trim($data['active'])) : '';

        return [
            'primary' => $primary,
            'active' => $active,
            'accounts' => $accounts,
            'legacy' => $legacy,
        ];
    }

    /**
     * @param array<string, string> $accounts
     */
    private function writeDocument(string $userId, string $primary, string $active, array $accounts): void
    {
        $normalized = $this->normalizeUserId($userId);
        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->relativeDir . '/' . $normalized . '.json',
            JsonHelper::encode([
                'schema' => self::SCHEMA,
                'userId' => $normalized,
                'primary' => $primary,
                'active' => $active,
                'accounts' => $accounts,
            ]),
            false
        );
    }

    private function writeLegacy(string $userId, string $password): void
    {
        $normalized = $this->normalizeUserId($userId);
        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->relativeDir . '/' . $normalized . '.json',
            JsonHelper::encode([
                'schema' => self::LEGACY_SCHEMA,
                'userId' => $normalized,
                'password' => $this->encryption->encrypt($password),
            ]),
            false
        );
    }

    /**
     * @param array{primary: string, active: string, accounts: array<string, string>, legacy: string} $doc
     */
    private function isPrimary(array $doc, string $mailbox): bool
    {
        return $doc['primary'] !== '' && $doc['primary'] === $mailbox;
    }

    private function path(string $userId): string
    {
        return $this->relativeDir . '/' . $this->normalizeUserId($userId) . '.json';
    }

    private function assertMailboxKey(string $mailbox): string
    {
        $mailbox = strtolower(trim($mailbox));
        if ($mailbox === '' || strlen($mailbox) > 254 || strpbrk($mailbox, "\r\n") !== false) {
            throw new InvalidArgumentException('Mailbox is invalid.');
        }
        if (filter_var($mailbox, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Mailbox is invalid.');
        }

        return $mailbox;
    }

    private function normalizeUserId(string $userId): string
    {
        $userId = trim($userId);
        if ($userId === '' || strlen($userId) > 256) {
            throw new InvalidArgumentException('User id is invalid.');
        }

        return 'u_' . substr(hash('sha256', $userId), 0, 32);
    }
}
