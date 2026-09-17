<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Media\Services\DamMediaUrl;
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
    private const IMAP_LIST_LIMIT_DEFAULT = 40;
    private const IMAP_LIST_LIMIT_MIN = 10;
    private const IMAP_LIST_LIMIT_MAX = 500;

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private MailboxSecretRepository $secrets,
        private SecurityAuditStore $audit,
        private MailClientStateRepository $clientState,
        private FileReaderInterface $files,
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
            'listLimit' => $this->imapListMessageLimit(),
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
        $folderRows = [];
        try {
            $folders = $client->folders();
            $this->auditOpen($user, 'folders');
            $hiddenFolders = $this->clientState->hiddenFolders($user->getId(), $this->clientMailbox($user));
            foreach ($folders as $folder) {
                if (in_array($folder['name'], $hiddenFolders, true)) {
                    continue;
                }
                $row = $folder;
                try {
                    $status = $client->folderStatus($folder['name']);
                    $row['total'] = $status['messages'];
                    $row['unseen'] = $status['unseen'];
                } catch (\Throwable) {
                    $row['total'] = 0;
                    $row['unseen'] = 0;
                }
                $bucket = $this->localFolderBucket($folder['name']);
                if ($bucket !== null) {
                    $fallback = count($this->clientState->localMessages(
                        $user->getId(),
                        $this->clientMailbox($user),
                        $bucket
                    ));
                    $row['total'] = $row['total'] + $fallback;
                }
                $folderRows[] = $row;
            }
        } finally {
            $client->disconnect();
        }

        $out = $folderRows;
        $hidden = $this->clientState->hiddenMessages($user->getId(), $this->clientMailbox($user));
        $out[] = [
            'name' => MailClientStateRepository::LOCAL_TRASH,
            'spam' => false,
            'virtual' => true,
            'total' => count($hidden),
            'unseen' => 0,
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
            $messages = $client->messages($folder, $this->imapListMessageLimit());
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
            if ($this->shouldSkipListedMessage($user, $folder, $row)) {
                continue;
            }
            $presented[] = MailMimeDecoder::present($row, false);
        }

        $bucket = $this->localFolderBucket($folder);
        if ($bucket !== null) {
            foreach ($this->clientState->localMessages($user->getId(), $this->clientMailbox($user), $bucket) as $local) {
                $presented[] = $this->presentLocalMessage($local, false);
            }
            usort($presented, static function (array $a, array $b): int {
                return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
            });
        }

        return ['folder' => $folder, 'messages' => $presented];
    }

    /**
     * @return array<string, mixed>
     */
    public function message(User $user, string $folder, int $uid, bool $allowRemoteImages = false): array
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === MailClientStateRepository::LOCAL_TRASH) {
            throw new InvalidArgumentException('Open this message from its original folder.');
        }
        if (MailClientStateRepository::isLocalUid($uid)) {
            $bucket = $this->localFolderBucket($folder);
            if ($bucket === null) {
                throw new InvalidArgumentException('Message is not available.');
            }
            $local = $this->clientState->findLocalMessage($user->getId(), $uid, $this->clientMailbox($user));
            if ($local === null || ($local['bucket'] ?? '') !== $bucket) {
                throw new InvalidArgumentException('Message is not available.');
            }

            return ['message' => $this->presentLocalMessage($local, true)];
        }
        $client = $this->connect($user);
        try {
            $message = $client->message($folder, $uid);
            if ($this->shouldSkipListedMessage($user, $folder, $message)) {
                throw new InvalidArgumentException('Message is not available.');
            }
            try {
                $client->addFlags($folder, $uid, ['seen']);
                $message['seen'] = true;
            } catch (\Throwable) {
            }
        } finally {
            $client->disconnect();
        }
        $this->auditOpen($user, 'read:' . $folder);

        return ['message' => MailMimeDecoder::present($message, true, $allowRemoteImages)];
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

    /**
     * Permanently dismiss all messages in local trash for this mailbox (they stay off IMAP lists in this client).
     *
     * @return array{removed: int}
     */
    public function emptyLocalTrash(User $user): array
    {
        $this->assertEnabled();
        $removed = $this->clientState->purgeHiddenMessages($user->getId(), $this->clientMailbox($user));
        if ($removed > 0) {
            $this->auditOpen($user, 'empty-local-trash:' . $removed);
        }

        return ['removed' => $removed];
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

    /**
     * Block sender for the active mailbox, then move the message to server spam.
     *
     * @return array{blocked: string, moved: bool}
     */
    public function blockSender(User $user, string $folder, int $uid, string $from): array
    {
        $folder = $this->normalizeFolder($folder);
        if ($folder === MailClientStateRepository::LOCAL_TRASH) {
            throw new InvalidArgumentException('Cannot block sender from local trash.');
        }
        $email = $this->clientState->blockSender($user->getId(), $from, $this->clientMailbox($user));
        $moved = false;
        try {
            $this->moveToSpam($user, $folder, $uid);
            $moved = true;
        } catch (\Throwable) {
        }
        $this->auditOpen($user, 'block:' . LogSanitizer::value($email));

        return ['blocked' => $email, 'moved' => $moved];
    }

    /**
     * @return array{mailbox: string, blocked: list<string>}
     */
    public function blockedSenders(User $user): array
    {
        return [
            'mailbox' => $this->mailbox($user),
            'blocked' => $this->clientState->blockedSenders($user->getId(), $this->clientMailbox($user)),
        ];
    }

    /**
     * @return array{unblocked: string, removed: bool}
     */
    public function unblockSender(User $user, string $email): array
    {
        $parsed = MailFromAddress::parse($email);
        $removed = $this->clientState->unblockSender($user->getId(), $parsed, $this->clientMailbox($user));
        if ($removed) {
            $this->auditOpen($user, 'unblock:' . LogSanitizer::value($parsed));
        }

        return ['unblocked' => $parsed, 'removed' => $removed];
    }

    /**
     * @return array<string, mixed>
     */
    public function signature(User $user): array
    {
        $mailbox = $this->mailbox($user);
        $prefs = $this->clientState->signaturePrefs($user->getId(), $this->clientMailbox($user));
        $fields = $this->signatureFields($user, $mailbox, $prefs['overrides']);
        $preview = $this->signaturePreviewHtml($prefs['templateId'], $fields);

        return [
            'mailbox' => $mailbox,
            'templates' => MailSignatureRenderer::templates(),
            'prefs' => $prefs,
            'fields' => $fields,
            'previewHtml' => $preview,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function saveSignature(User $user, array $payload): array
    {
        $patch = [];
        if (array_key_exists('enabled', $payload)) {
            $patch['enabled'] = filter_var($payload['enabled'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('templateId', $payload)) {
            $templateId = trim((string) ($payload['templateId'] ?? ''));
            if (!MailSignatureRenderer::isValidTemplate($templateId)) {
                throw new InvalidArgumentException('Signature template is invalid.');
            }
            $patch['templateId'] = $templateId;
        }
        if (array_key_exists('overrides', $payload)) {
            $overrides = $payload['overrides'];
            if (!is_array($overrides)) {
                throw new InvalidArgumentException('Signature overrides must be an object.');
            }
            $patch['overrides'] = MailSignatureFieldMerge::normalizeOverrides($overrides, $this->publicSiteUrl());
        }
        $this->clientState->saveSignaturePrefs($user->getId(), $patch, $this->clientMailbox($user));

        return $this->signature($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function importSignatureFromProfile(User $user): array
    {
        $this->clientState->clearSignatureOverrides($user->getId(), $this->clientMailbox($user));

        return $this->signature($user);
    }

    /**
     * Mark every message currently in the server spam folder as purged for this mailbox (reload autoclean).
     *
     * @return array{purged: int}
     */
    public function autocleanSpam(User $user): array
    {
        $client = $this->connect($user);
        try {
            $spamFolder = $this->spamFolderName($client);
            $uids = [];
            foreach ($client->messages($spamFolder, 500) as $row) {
                $uid = (int) ($row['uid'] ?? 0);
                if ($uid > 0) {
                    $uids[] = $uid;
                }
            }
        } finally {
            $client->disconnect();
        }
        if ($uids === []) {
            return ['purged' => 0];
        }
        $this->clientState->addPurgedSpamUids($user->getId(), $uids, $this->clientMailbox($user));
        $this->auditOpen($user, 'spam-autoclean:' . count($uids));

        return ['purged' => count($uids)];
    }

    /**
     * @return array{sent: bool, localUid: int, imapAppended: bool}
     */
    public function send(User $user, string $to, string $subject, string $body): array
    {
        $this->assertEnabled();
        $mailbox = $this->mailbox($user);
        SiteMailboxGuard::assertMailbox($mailbox, $this->siteHost());
        if (!$this->isSmtpReady()) {
            throw new InvalidArgumentException('SMTP is disabled.');
        }

        $recipients = MailRecipientParser::parse($to);
        if ($recipients === []) {
            throw new InvalidArgumentException('Recipient is invalid.');
        }
        $toHeader = implode(', ', $recipients);
        $subject = trim($subject);
        $subject = $this->assertHeaderText($subject === '' ? '(no subject)' : $subject, 'Subject', 200);
        $body = trim($body);
        if ($body === '' || strlen($body) > 100000) {
            throw new InvalidArgumentException('Message body is invalid.');
        }

        $prefs = $this->clientState->signaturePrefs($user->getId(), $this->clientMailbox($user));
        $fields = $this->signatureFields($user, $mailbox, $prefs['overrides']);
        $fromName = $fields['displayName'];
        if ($fromName === '' || strpbrk($fromName, "\r\n") !== false) {
            $fromName = $mailbox;
        }

        $html = '<div style="font-family:Arial,sans-serif">'
            . '<pre style="font-family:inherit;white-space:pre-wrap;margin:0">'
            . htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</pre>';
        $inlineImages = [];
        if ($prefs['enabled']) {
            $signatureFields = $fields;
            if ($fields['avatarUrl'] !== '' && MailSignatureRenderer::usesAvatar($prefs['templateId'])) {
                $loaded = MailSignatureInlineAvatarLoader::load($fields['avatarUrl'], $this->files->getBasePath());
                if ($loaded !== null) {
                    $inlineImages[] = $loaded;
                    $signatureFields['avatarUrl'] = MailSignatureInlineAvatarLoader::cidSrc();
                }
            }
            $signature = MailSignatureRenderer::render($prefs['templateId'], $signatureFields);
            if ($signature !== '') {
                $html .= $signature;
            }
        }
        $html .= '</div>';

        $sent = $this->outbound()->send($mailbox, $fromName, $recipients, $subject, $html, $inlineImages);
        if (!$sent) {
            throw new RuntimeException('Could not send mail.');
        }

        $imapAppended = false;
        if ($this->shouldAppendSentOnSend()) {
            $imapAppended = $this->tryAppendSentCopy($user, $mailbox, $fromName, $recipients, $subject, $html, $inlineImages);
        }
        $localUid = 0;
        if (!$imapAppended) {
            $fromHeader = $fromName !== '' && $fromName !== $mailbox
                ? $fromName . ' <' . $mailbox . '>'
                : $mailbox;
            $displayHtml = MailSignatureInlineAvatarLoader::embedInHtmlForDisplay($html, $inlineImages);
            $localUid = $this->clientState->addLocalSent($user->getId(), [
                'to' => $toHeader,
                'from' => $fromHeader,
                'subject' => $subject,
                'body' => $body,
                'html' => MailHtmlSanitizer::document($displayHtml, true),
                'date' => gmdate('D, d M Y H:i:s O'),
            ], $this->clientMailbox($user));
        }

        $this->audit->append(
            'mail.send',
            'info',
            'Mailbox send',
            $user->getId(),
            LogSanitizer::value($mailbox),
            null,
            [
                'to' => LogSanitizer::value($toHeader),
                'imapAppend' => $imapAppended ? '1' : '0',
            ]
        );

        return ['sent' => true, 'localUid' => $localUid, 'imapAppended' => $imapAppended];
    }

    /**
     * @return array{uid: int}
     */
    public function saveDraft(User $user, string $to, string $subject, string $body, int $draftUid = 0): array
    {
        $this->assertEnabled();
        $mailbox = $this->mailbox($user);
        SiteMailboxGuard::assertMailbox($mailbox, $this->siteHost());
        $to = trim($to);
        if ($to !== '') {
            $to = MailRecipientParser::normalizeDraftRecipients($to);
        }
        $subject = $this->assertHeaderText($subject === '' ? '(no subject)' : $subject, 'Subject', 200);
        $body = trim($body);
        if (strlen($body) > 100000) {
            throw new InvalidArgumentException('Message body is invalid.');
        }
        $fromHeader = $mailbox;
        $html = '<pre style="white-space:pre-wrap;font-family:inherit;margin:0">'
            . htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</pre>';
        $uid = $this->clientState->saveLocalDraft($user->getId(), [
            'to' => $to,
            'from' => $fromHeader,
            'subject' => $subject,
            'body' => $body,
            'html' => $html,
        ], $this->clientMailbox($user), $draftUid);

        return ['uid' => $uid];
    }

    public function deleteLocalMessage(User $user, string $folder, int $uid): void
    {
        if (!MailClientStateRepository::isLocalUid($uid)) {
            throw new InvalidArgumentException('Only client-local copies can be deleted permanently.');
        }
        $bucket = $this->localFolderBucket($folder);
        if ($bucket === null) {
            throw new InvalidArgumentException('Folder is invalid.');
        }
        $local = $this->clientState->findLocalMessage($user->getId(), $uid, $this->clientMailbox($user));
        if ($local === null || ($local['bucket'] ?? '') !== $bucket) {
            throw new InvalidArgumentException('Message is not available.');
        }
        if (!$this->clientState->deleteLocalMessage($user->getId(), $uid, $this->clientMailbox($user))) {
            throw new InvalidArgumentException('Message is not available.');
        }
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

    /**
     * @param array<string, mixed> $row
     */
    private function shouldSkipListedMessage(User $user, string $folder, array $row): bool
    {
        $mailbox = $this->clientMailbox($user);
        $from = (string) ($row['from'] ?? '');
        if ($from !== '' && $this->clientState->isSenderBlocked($user->getId(), $from, $mailbox)) {
            return true;
        }
        $uid = (int) ($row['uid'] ?? 0);
        if ($uid > 0 && SiteMailboxGuard::isSpamFolder($folder) && $this->clientState->isSpamPurged($user->getId(), $uid, $mailbox)) {
            return true;
        }
        if ($uid > 0 && $this->clientState->isClientMessagePurged($user->getId(), $folder, $uid, $mailbox)) {
            return true;
        }

        return false;
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

    /**
     * @param array<string, string> $overrides
     * @return array{
     *   displayName: string,
     *   jobTitle: string,
     *   phone: string,
     *   contactEmail: string,
     *   bio: string,
     *   companyName: string,
     *   website: string,
     *   avatarUrl: string
     * }
     */
    private function signatureFields(User $user, string $activeMailbox, array $overrides): array
    {
        return MailSignatureFieldMerge::resolve(
            $user,
            $activeMailbox,
            $this->settings->group('company'),
            $overrides,
            $this->publicSiteUrl()
        );
    }

    /**
     * @param array{
     *   displayName: string,
     *   jobTitle: string,
     *   phone: string,
     *   contactEmail: string,
     *   bio: string,
     *   companyName: string,
     *   website: string,
     *   avatarUrl: string
     * } $fields
     */
    private function signaturePreviewHtml(string $templateId, array $fields): string
    {
        if (MailSignatureRenderer::usesAvatar($templateId) && $fields['avatarUrl'] !== '') {
            $sanitized = DamMediaUrl::sanitize($fields['avatarUrl']);
            if ($sanitized !== '' && str_starts_with($sanitized, '/')) {
                $base = rtrim($this->publicSiteUrl(), '/');
                $fields['avatarUrl'] = $base !== '' ? $base . $sanitized : $sanitized;
            }
        }

        return MailSignatureRenderer::render($templateId, $fields);
    }

    private function publicSiteUrl(): string
    {
        return (string) ($this->settings->group('general')['siteUrl'] ?? '');
    }

    private function imapListMessageLimit(): int
    {
        $raw = (int) ($this->imapSettings()['listLimit'] ?? self::IMAP_LIST_LIMIT_DEFAULT);
        if ($raw <= 0) {
            return self::IMAP_LIST_LIMIT_DEFAULT;
        }

        return max(self::IMAP_LIST_LIMIT_MIN, min(self::IMAP_LIST_LIMIT_MAX, $raw));
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

    private function shouldAppendSentOnSend(): bool
    {
        return $this->settingEnabled($this->imapSettings()['appendSentOnSend'] ?? true);
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

    /**
     * @param array<string, mixed> $local
     * @return array<string, mixed>
     */
    private function presentLocalMessage(array $local, bool $withBody): array
    {
        $row = [
            'uid' => (int) $local['uid'],
            'subject' => (string) $local['subject'],
            'from' => (string) $local['from'],
            'date' => (string) $local['date'],
            'tags' => is_array($local['tags'] ?? null) ? $local['tags'] : [],
            'seen' => true,
            'flagged' => false,
            'localOnly' => true,
            'to' => (string) ($local['to'] ?? ''),
        ];
        if (!$withBody) {
            $presented = MailMimeDecoder::present([
                ...$row,
                'snippet' => MailMimeDecoder::snippet((string) ($local['body'] ?? $local['subject'])),
            ], false);
            $presented['localOnly'] = true;
            $presented['to'] = $row['to'];

            return $presented;
        }

        $html = (string) ($local['html'] ?? '');
        if ($html === '') {
            $html = '<pre style="white-space:pre-wrap;font-family:inherit;margin:0">'
                . htmlspecialchars((string) ($local['body'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</pre>';
        }
        $presented = MailMimeDecoder::present([
            ...$row,
            'body' => (string) ($local['body'] ?? ''),
            'html' => MailHtmlSanitizer::document($html, true),
        ], true);
        $presented['localOnly'] = true;
        $presented['to'] = $row['to'];

        return $presented;
    }

    /**
     * @param list<string> $recipients
     * @param list<array{contentId: string, mime: string, bytes: string}> $inlineImages
     */
    private function tryAppendSentCopy(
        User $user,
        string $mailbox,
        string $fromName,
        array $recipients,
        string $subject,
        string $html,
        array $inlineImages,
    ): bool {
        try {
            $rfc822 = MailOutboundMimeBuilder::buildRfc822($mailbox, $fromName, $recipients, $subject, $html, $inlineImages);
            $client = $this->connect($user);
            try {
                $sentFolder = $this->resolveSentFolder($client);
                if ($sentFolder === null) {
                    return false;
                }
                $client->appendMessage($sentFolder, $rfc822, ['\\Seen']);
            } finally {
                $client->disconnect();
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveSentFolder(ImapClientInterface $client): ?string
    {
        foreach ($client->folders() as $folder) {
            $name = $folder['name'];
            if (str_contains(strtolower($name), 'sent')) {
                return $name;
            }
        }

        return null;
    }

    private function localFolderBucket(string $folder): ?string
    {
        $key = strtolower($folder);
        if (str_contains($key, 'sent')) {
            return MailClientStateRepository::LOCAL_BUCKET_SENT;
        }
        if (str_contains($key, 'draft')) {
            return MailClientStateRepository::LOCAL_BUCKET_DRAFT;
        }

        return null;
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
