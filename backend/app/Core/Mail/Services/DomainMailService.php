<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\Notification\Services\SmtpTransport;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Domain IMAP mailbox (It.93m). Proxy only — never dumps .eml into data/.
 */
final class DomainMailService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private MailboxSecretRepository $secrets,
        private SecurityAuditStore $audit,
        private MailClientStateRepository $clientState,
        private ?ImapClientInterface $clientOverride = null,
        private ?OutboundMailSenderInterface $senderOverride = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(User $user): array
    {
        $imap = $this->imapSettings();
        $siteHost = $this->siteHost();
        $primary = $this->primaryMailbox($user);
        $mailbox = $this->mailbox($user);
        $mailboxAllowed = SiteMailboxGuard::isMailboxAllowed($mailbox, $siteHost);
        $smtpReady = $this->isSmtpReady();

        return [
            'enabled' => $this->isImapEnabled(),
            'configured' => (string) ($imap['host'] ?? '') !== '',
            'siteHost' => $siteHost,
            'mailbox' => $mailbox,
            'mailboxAllowed' => $mailboxAllowed,
            'hasPassword' => $this->secrets->getPassword($user->getId(), $mailbox) !== null,
            'canReadAll' => $user->isAdmin(),
            'smtpEnabled' => $smtpReady,
            'canSend' => $smtpReady && $mailboxAllowed,
            'accounts' => $this->secrets->listAccounts($user->getId(), $primary),
        ];
    }

    public function savePassword(User $user, string $password): void
    {
        $this->assertEnabled();
        $mailbox = $this->mailbox($user);
        SiteMailboxGuard::assertMailbox($mailbox, $this->siteHost());
        $password = trim($password);
        if ($password === '') {
            throw new InvalidArgumentException('Mailbox password is required.');
        }
        $this->secrets->savePassword($user->getId(), $password, $mailbox);
    }

    public function addAccount(User $user, string $mailbox, string $password): void
    {
        $this->assertEnabled();
        $mailbox = strtolower(trim($mailbox));
        SiteMailboxGuard::assertMailbox($mailbox, $this->siteHost());
        $this->secrets->addAccount($user->getId(), $mailbox, $password, $this->primaryMailbox($user));
    }

    public function removeAccount(User $user, string $mailbox): void
    {
        $this->secrets->removeAccount($user->getId(), $mailbox, $this->primaryMailbox($user));
    }

    public function selectAccount(User $user, string $mailbox): void
    {
        $mailbox = strtolower(trim($mailbox));
        SiteMailboxGuard::assertMailbox($mailbox, $this->siteHost());
        $this->secrets->setActive($user->getId(), $mailbox, $this->primaryMailbox($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function folders(User $user): array
    {
        $client = $this->connect($user);
        try {
            $folders = $client->folders();
        } finally {
            $client->disconnect();
        }

        $this->auditOpen($user, 'folders');
        $hiddenFolders = $this->clientState->hiddenFolders($user->getId(), $this->clientMailbox($user));
        $out = [];
        foreach ($folders as $folder) {
            if (in_array($folder['name'], $hiddenFolders, true)) {
                continue;
            }
            $out[] = $folder;
        }
        $out[] = [
            'name' => MailClientStateRepository::LOCAL_TRASH,
            'spam' => false,
            'virtual' => true,
        ];

        return ['folders' => $out, 'mailbox' => $this->mailbox($user)];
    }

    public function createFolder(User $user, string $name): void
    {
        $client = $this->connect($user);
        try {
            $client->createFolder($name);
        } finally {
            $client->disconnect();
        }
    }

    public function deleteFolder(User $user, string $name): void
    {
        $name = $this->normalizeFolder($name);
        if ($this->isProtectedFolder($name)) {
            throw new InvalidArgumentException('This folder cannot be deleted.');
        }
        $client = $this->connect($user);
        try {
            $client->deleteFolder($name);
        } finally {
            $client->disconnect();
        }
        $this->clientState->hideFolder($user->getId(), $name, $this->clientMailbox($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function messages(User $user, string $folder): array
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === MailClientStateRepository::LOCAL_TRASH) {
            $hidden = [];
            foreach ($this->clientState->hiddenMessages($user->getId(), $this->clientMailbox($user)) as $row) {
                $hidden[] = MailMimeDecoder::present([
                    'uid' => $row['uid'],
                    'subject' => $row['subject'],
                    'from' => $row['from'],
                    'date' => $row['date'],
                    'tags' => [],
                    'seen' => true,
                    'flagged' => false,
                    'originFolder' => $row['folder'],
                ], false);
            }

            return ['folder' => $folder, 'messages' => $hidden];
        }

        $client = $this->connect($user);
        try {
            $messages = $client->messages($folder);
        } finally {
            $client->disconnect();
        }
        $this->auditOpen($user, 'list:' . $folder);
        $hidden = $this->clientState->hiddenUids($user->getId(), $folder, $this->clientMailbox($user));
        $presented = [];
        foreach ($messages as $row) {
            $uid = (int) ($row['uid'] ?? 0);
            if (in_array($uid, $hidden, true)) {
                continue;
            }
            $presented[] = MailMimeDecoder::present($row, false);
        }

        return ['folder' => $folder, 'messages' => $presented];
    }

    /**
     * @return array<string, mixed>
     */
    public function message(User $user, string $folder, int $uid): array
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === MailClientStateRepository::LOCAL_TRASH) {
            throw new InvalidArgumentException('Open this message from its original folder.');
        }
        $client = $this->connect($user);
        try {
            $message = $client->message($folder, $uid);
            try {
                $client->addFlags($folder, $uid, ['seen']);
                $message['seen'] = true;
            } catch (\Throwable) {
            }
        } finally {
            $client->disconnect();
        }
        $this->auditOpen($user, 'read:' . $folder);

        return ['message' => MailMimeDecoder::present($message, true)];
    }

    /**
     * @param list<string> $tags
     */
    public function tag(User $user, string $folder, int $uid, array $tags): void
    {
        $client = $this->connect($user);
        try {
            $client->addFlags($this->normalizeFolder($folder), $uid, $tags);
        } finally {
            $client->disconnect();
        }
    }

    /**
     * @param list<string> $add
     * @param list<string> $remove
     */
    public function changeFlags(User $user, string $folder, int $uid, array $add, array $remove): void
    {
        $folder = $this->normalizeFolder($folder);
        $client = $this->connect($user);
        try {
            if ($add !== []) {
                $client->addFlags($folder, $uid, $add);
            }
            if ($remove !== []) {
                $client->removeFlags($folder, $uid, $remove);
            }
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Local hide only — IMAP message stays on the server.
     */
    public function hideMessage(User $user, string $folder, int $uid, ?string $subject = null, ?string $from = null, ?string $date = null): void
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === MailClientStateRepository::LOCAL_TRASH) {
            throw new InvalidArgumentException('Message is already in local trash.');
        }
        $this->clientState->hideMessage($user->getId(), $folder, $uid, [
            'subject' => $subject ?? '',
            'from' => $from ?? '',
            'date' => $date ?? '',
        ], $this->clientMailbox($user));
    }

    public function unhideMessage(User $user, string $folder, int $uid): void
    {
        $this->clientState->unhideMessage($user->getId(), $this->normalizeFolder($folder), $uid, $this->clientMailbox($user));
    }

    public function moveToSpam(User $user, string $folder, int $uid): void
    {
        $client = $this->connect($user);
        try {
            $spam = $this->spamFolderName($client);
            $client->move($this->normalizeFolder($folder), $uid, $spam);
        } finally {
            $client->disconnect();
        }
    }

    public function send(User $user, string $to, string $subject, string $body): void
    {
        $this->assertEnabled();
        $mailbox = $this->mailbox($user);
        SiteMailboxGuard::assertMailbox($mailbox, $this->siteHost());
        if (!$this->isSmtpReady()) {
            throw new InvalidArgumentException('SMTP is disabled.');
        }

        $to = $this->assertHeaderEmail($to, 'Recipient');
        $subject = $this->assertHeaderText($subject, 'Subject', 200);
        $body = trim($body);
        if ($body === '' || strlen($body) > 100000) {
            throw new InvalidArgumentException('Message body is invalid.');
        }

        $fromName = trim($user->getName());
        if ($fromName === '' || strpbrk($fromName, "\r\n") !== false) {
            $fromName = $mailbox;
        }

        $html = '<pre style="font-family:inherit;white-space:pre-wrap">'
            . htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</pre>';

        $sent = $this->outbound()->send($mailbox, $fromName, $to, $subject, $html);
        if (!$sent) {
            throw new RuntimeException('Could not send mail.');
        }

        $this->audit->append(
            'mail.send',
            'info',
            'Mailbox send',
            $user->getId(),
            LogSanitizer::value($mailbox),
            null,
            ['to' => LogSanitizer::value($to)]
        );
    }

    private function connect(User $user): ImapClientInterface
    {
        $this->assertEnabled();
        $mailbox = $this->mailbox($user);
        SiteMailboxGuard::assertMailbox($mailbox, $this->siteHost());
        $imap = $this->imapSettings();
        $host = (string) ($imap['host'] ?? '');
        $port = (int) ($imap['port'] ?? 993);
        $encryption = (string) ($imap['encryption'] ?? 'ssl');
        $env = (string) (getenv('APP_ENV') ?: 'production');
        $allowPrivate = in_array($env, ['testing', 'test', 'development', 'local'], true);
        SiteMailboxGuard::assertImapHost($host, $this->siteHost(), $allowPrivate);

        $password = $this->secrets->getPassword($user->getId(), $mailbox);
        if ($password === null || $password === '') {
            throw new InvalidArgumentException('Mailbox password is not set.');
        }

        $client = $this->clientOverride ?? $this->makeClient($host);
        $client->connect($host, $port, $encryption, $mailbox, $password);

        return $client;
    }

    private function makeClient(string $host): ImapClientInterface
    {
        if ($host === 'imap.paginium.test') {
            return new FakeImapClient();
        }

        return new StreamImapClient();
    }

    private function spamFolderName(ImapClientInterface $client): string
    {
        $configured = trim((string) ($this->imapSettings()['spamFolder'] ?? 'Junk'));
        foreach ($client->folders() as $folder) {
            if ($folder['spam'] === true) {
                return $folder['name'];
            }
        }

        return $configured !== '' ? $configured : 'Junk';
    }

    /**
     * @return array<string, mixed>
     */
    private function imapSettings(): array
    {
        return $this->settings->group('imap');
    }

    private function primaryMailbox(User $user): string
    {
        return strtolower($user->getEmail());
    }

    private function mailbox(User $user): string
    {
        return $this->secrets->activeMailbox($user->getId(), $this->primaryMailbox($user));
    }

    private function clientMailbox(User $user): string
    {
        $active = $this->mailbox($user);
        $primary = $this->primaryMailbox($user);

        return $active === $primary ? '' : $active;
    }

    private function siteHost(): string
    {
        $imap = $this->imapSettings();

        return SiteMailboxGuard::siteHost(
            (string) ($imap['allowedDomain'] ?? ''),
            (string) ($this->settings->group('general')['siteUrl'] ?? ''),
            (string) ($this->settings->group('company')['website'] ?? ''),
            (string) ($imap['host'] ?? '')
        );
    }

    private function assertEnabled(): void
    {
        if (!$this->isImapEnabled()) {
            throw new InvalidArgumentException('IMAP is disabled.');
        }
    }

    private function isImapEnabled(): bool
    {
        return $this->settingEnabled($this->imapSettings()['enabled'] ?? false);
    }

    private function isSmtpReady(): bool
    {
        $smtp = $this->settings->group('smtp');
        if (!$this->settingEnabled($smtp['enabled'] ?? false)) {
            return false;
        }

        return trim((string) ($smtp['host'] ?? '')) !== '';
    }

    private function settingEnabled(mixed $raw): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_int($raw) || is_float($raw)) {
            return $raw !== 0;
        }
        if (is_string($raw)) {
            return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }

    private function outbound(): OutboundMailSenderInterface
    {
        if ($this->senderOverride instanceof OutboundMailSenderInterface) {
            return $this->senderOverride;
        }

        $smtp = $this->settings->group('smtp');
        $host = trim((string) ($smtp['host'] ?? ''));
        if (!$this->isSmtpReady() || $host === '') {
            throw new InvalidArgumentException('SMTP is disabled.');
        }

        return new SmtpOutboundMailSender(new SmtpTransport(
            $host,
            (int) ($smtp['port'] ?? 587),
            (string) ($smtp['encryption'] ?? 'tls'),
            (string) ($smtp['username'] ?? ''),
            (string) ($smtp['password'] ?? ''),
        ));
    }

    private function assertHeaderEmail(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '' || strpbrk($value, "\r\n") !== false || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException($label . ' is invalid.');
        }

        return $value;
    }

    private function assertHeaderText(string $value, string $label, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '' || strpbrk($value, "\r\n") !== false || strlen($value) > $maxLength) {
            throw new InvalidArgumentException($label . ' is invalid.');
        }

        return $value;
    }

    private function isProtectedFolder(string $name): bool
    {
        $lower = strtolower($name);
        if (in_array($lower, ['inbox', 'sent', 'drafts', 'trash', 'junk', 'spam', MailClientStateRepository::LOCAL_TRASH], true)) {
            return true;
        }

        return SiteMailboxGuard::isSpamFolder($name);
    }

    private function normalizeFolder(string $folder): string
    {
        $folder = trim($folder);
        if ($folder === '') {
            return 'INBOX';
        }
        if (strlen($folder) > 80 || str_contains($folder, "\n") || str_contains($folder, '"')) {
            throw new InvalidArgumentException('Folder is invalid.');
        }

        return $folder;
    }

    private function auditOpen(User $user, string $action): void
    {
        $this->audit->append(
            'mail.read',
            'info',
            'Mailbox access',
            $user->getId(),
            LogSanitizer::value($user->getEmail()),
            null,
            ['action' => LogSanitizer::value($action)]
        );
    }
}
