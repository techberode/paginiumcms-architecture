<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Mail\Services\DomainMailService;
use PaginiumCMS\Core\Mail\Services\FakeImapClient;
use PaginiumCMS\Core\Mail\Services\MailboxSecretRepository;
use PaginiumCMS\Core\Mail\Services\MailClientStateRepository;
use PaginiumCMS\Core\Mail\Services\OutboundMailSenderInterface;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PHPUnit\Framework\TestCase;

final class DomainMailServiceTest extends TestCase
{
    private string $baseDir;
    private DomainMailService $service;
    private User $user;
    private RecordingOutboundMailSender $sender;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        $this->baseDir = sys_get_temp_dir() . '/pag_mailsvc_' . uniqid('', true);
        mkdir($this->baseDir . '/data/security', 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $secrets = new MailboxSecretRepository(
            $reader,
            $writer,
            new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=')
        );
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            return match ($group) {
                'imap' => [
                    'enabled' => true,
                    'host' => 'imap.paginium.test',
                    'port' => 993,
                    'encryption' => 'ssl',
                    'spamFolder' => 'Junk',
                ],
                'general' => ['siteUrl' => 'https://paginium.test'],
                'smtp' => [
                    'enabled' => true,
                    'host' => 'smtp.paginium.test',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'editor@paginium.test',
                    'password' => 'smtp-pass',
                    'fromEmail' => 'noreply@paginium.test',
                    'fromName' => 'Site',
                ],
                default => [],
            };
        });

        $this->user = new User();
        $this->user->setEmail('editor@paginium.test');
        $this->user->setName('Editor');
        $this->user->setPasswordHash(password_hash('x', PASSWORD_BCRYPT));

        $this->sender = new RecordingOutboundMailSender();
        $this->service = new DomainMailService(
            $settings,
            $secrets,
            new SecurityAuditStore($reader),
            new MailClientStateRepository($reader, $writer),
            new FakeImapClient(),
            $this->sender
        );
        $this->service->savePassword($this->user, 'mailbox-pass');
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testListsFoldersAndMovesToSpam(): void
    {
        $folders = $this->service->folders($this->user);
        $names = array_column($folders['folders'], 'name');
        $this->assertContains('INBOX', $names);
        $this->assertContains('Junk', $names);

        $list = $this->service->messages($this->user, 'INBOX');
        $this->assertSame(1, $list['messages'][0]['uid'] ?? null);

        $this->service->tag($this->user, 'INBOX', 1, ['urgent']);
        $message = $this->service->message($this->user, 'INBOX', 1);
        $this->assertContains('urgent', $message['message']['tags'] ?? []);

        $this->service->moveToSpam($this->user, 'INBOX', 1);
        $inbox = $this->service->messages($this->user, 'INBOX');
        $this->assertSame([], $inbox['messages']);
        $junk = $this->service->messages($this->user, 'Junk');
        $this->assertSame(1, $junk['messages'][0]['uid'] ?? null);
    }

    public function testHidesMessageLocallyAndKeepsImapCopy(): void
    {
        $this->service->hideMessage($this->user, 'INBOX', 1, 'Welcome', 'noreply@example.com', '2026-09-15');
        $inbox = $this->service->messages($this->user, 'INBOX');
        $this->assertSame([], $inbox['messages']);
        $trash = $this->service->messages($this->user, MailClientStateRepository::LOCAL_TRASH);
        $this->assertSame(1, $trash['messages'][0]['uid'] ?? null);
        $this->service->unhideMessage($this->user, 'INBOX', 1);
        $restored = $this->service->messages($this->user, 'INBOX');
        $this->assertSame(1, $restored['messages'][0]['uid'] ?? null);
    }

    public function testStarsAndMarksRead(): void
    {
        $this->service->changeFlags($this->user, 'INBOX', 1, ['flagged', 'seen'], []);
        $message = $this->service->message($this->user, 'INBOX', 1);
        $this->assertTrue((bool) ($message['message']['flagged'] ?? false));
        $this->assertTrue((bool) ($message['message']['seen'] ?? false));
    }

    public function testRejectsGmailMailbox(): void
    {
        $user = new User();
        $user->setEmail('someone@gmail.com');
        $user->setPasswordHash(password_hash('x', PASSWORD_BCRYPT));

        $this->expectException(InvalidArgumentException::class);
        $this->service->savePassword($user, 'nope');
    }

    public function testSavesPasswordWhenSiteUrlIsLanIp(): void
    {
        $baseDir = sys_get_temp_dir() . '/pag_mailsvc_lan_' . uniqid('', true);
        mkdir($baseDir . '/data/security', 0777, true);
        $validator = new FileValidator($baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $secrets = new MailboxSecretRepository(
            $reader,
            $writer,
            new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=')
        );
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group): array {
            return match ($group) {
                'imap' => [
                    'enabled' => 'true',
                    'host' => 'mail.webland.fun',
                    'port' => 993,
                    'encryption' => 'ssl',
                    'spamFolder' => 'Junk',
                    'allowedDomain' => '',
                ],
                'general' => ['siteUrl' => 'http://192.168.10.26:8081'],
                default => [],
            };
        });
        $user = new User();
        $user->setEmail('hello@webland.fun');
        $user->setPasswordHash(password_hash('x', PASSWORD_BCRYPT));
        $service = new DomainMailService(
            $settings,
            $secrets,
            new SecurityAuditStore($reader),
            new MailClientStateRepository($reader, $writer),
            new FakeImapClient()
        );
        $service->savePassword($user, 'mailbox-pass');
        $status = $service->status($user);
        $this->assertTrue($status['enabled']);
        $this->assertTrue($status['mailboxAllowed']);
        $this->assertTrue($status['hasPassword']);
        $this->assertSame('mail.webland.fun', $status['siteHost']);
        $this->removeTree($baseDir);
    }

    public function testSendsThroughConfiguredSmtpAsMailbox(): void
    {
        $status = $this->service->status($this->user);
        $this->assertTrue($status['canSend']);
        $this->assertSame('editor@paginium.test', $status['mailbox']);

        $this->service->send($this->user, 'guest@example.com', 'Hello', 'Hi there');
        $this->assertCount(1, $this->sender->sent);
        $this->assertSame('editor@paginium.test', $this->sender->sent[0]['fromEmail']);
        $this->assertSame('Editor', $this->sender->sent[0]['fromName']);
        $this->assertSame('guest@example.com', $this->sender->sent[0]['to']);
        $this->assertSame('Hello', $this->sender->sent[0]['subject']);
        $this->assertStringContainsString('Hi there', $this->sender->sent[0]['htmlBody']);
    }

    public function testRejectsInjectedRecipientHeaders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->send($this->user, "guest@example.com\nBcc: hidden@example.com", 'Hello', 'Hi');
    }

    public function testAddsSecondDomainMailboxAndSendsAsIt(): void
    {
        $this->service->addAccount($this->user, 'info@paginium.test', 'info-pass');
        $status = $this->service->status($this->user);
        $this->assertSame('info@paginium.test', $status['mailbox']);
        $this->assertCount(2, $status['accounts']);

        $this->service->send($this->user, 'guest@example.com', 'From info', 'Body');
        $this->assertSame('info@paginium.test', $this->sender->sent[0]['fromEmail']);

        $this->service->selectAccount($this->user, 'editor@paginium.test');
        $this->assertSame('editor@paginium.test', $this->service->status($this->user)['mailbox']);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }
        rmdir($path);
    }
}

final class RecordingOutboundMailSender implements OutboundMailSenderInterface
{
    /** @var list<array{fromEmail: string, fromName: string, to: string, subject: string, htmlBody: string}> */
    public array $sent = [];

    public function send(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody): bool
    {
        $this->sent[] = [
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'to' => $to,
            'subject' => $subject,
            'htmlBody' => $htmlBody,
        ];

        return true;
    }
}
