<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Mail\Services\MailClientStateRepository;
use PHPUnit\Framework\TestCase;

final class MailClientStateRepositoryTest extends TestCase
{
    private string $baseDir;
    private MailClientStateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_mailcli_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->repository = new MailClientStateRepository(
            new FileReader($validator),
            new FileWriter($validator)
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testHidePersistsAcrossReads(): void
    {
        $this->repository->hideMessage('user_1', 'INBOX', 9, ['subject' => 'Hi', 'from' => 'a@b.c']);
        $this->assertSame([9], $this->repository->hiddenUids('user_1', 'INBOX'));
        $this->repository->unhideMessage('user_1', 'INBOX', 9);
        $this->assertSame([], $this->repository->hiddenUids('user_1', 'INBOX'));
    }

    public function testPurgeHiddenMessagesClearsTrashAndRecordsDismissed(): void
    {
        $this->repository->hideMessage('user_1', 'INBOX', 9, ['subject' => 'Hi', 'from' => 'a@b.c']);
        $this->assertSame(1, $this->repository->purgeHiddenMessages('user_1'));
        $this->assertSame([], $this->repository->hiddenMessages('user_1'));
        $this->assertTrue($this->repository->isClientMessagePurged('user_1', 'INBOX', 9));
    }

    public function testLocalSentAndDraftPersistUntilDeleted(): void
    {
        $sentUid = $this->repository->addLocalSent('user_1', [
            'to' => 'guest@example.com',
            'from' => 'Editor <editor@site.test>',
            'subject' => 'Hello',
            'body' => 'Body',
            'html' => '<p>Body</p>',
        ]);
        $this->assertLessThan(0, $sentUid);
        $sent = $this->repository->localMessages('user_1', '', MailClientStateRepository::LOCAL_BUCKET_SENT);
        $this->assertCount(1, $sent);
        $draftUid = $this->repository->saveLocalDraft('user_1', [
            'to' => '',
            'from' => 'editor@site.test',
            'subject' => 'Draft',
            'body' => 'Later',
            'html' => '<p>Later</p>',
        ]);
        $this->repository->saveLocalDraft('user_1', [
            'subject' => 'Draft updated',
            'body' => 'Updated',
            'html' => '<p>Updated</p>',
        ], '', $draftUid);
        $draft = $this->repository->findLocalMessage('user_1', $draftUid);
        $this->assertSame('Draft updated', $draft['subject'] ?? '');
        $this->assertTrue($this->repository->deleteLocalMessage('user_1', $sentUid));
        $this->assertSame([], $this->repository->localMessages('user_1', '', MailClientStateRepository::LOCAL_BUCKET_SENT));
    }

    public function testSignaturePrefsPersistPerMailbox(): void
    {
        $this->repository->saveSignaturePrefs('user_1', [
            'enabled' => true,
            'templateId' => 'brand',
            'overrides' => ['displayName' => 'Info Desk'],
        ], 'info@site.test');
        $prefs = $this->repository->signaturePrefs('user_1', 'info@site.test');
        $this->assertTrue($prefs['enabled']);
        $this->assertSame('brand', $prefs['templateId']);
        $this->assertSame('Info Desk', $prefs['overrides']['displayName']);
        $this->repository->clearSignatureOverrides('user_1', 'info@site.test');
        $this->assertSame([], $this->repository->signaturePrefs('user_1', 'info@site.test')['overrides']);
    }

    public function testPrimaryMailboxSignaturePersistsInRootDocument(): void
    {
        $this->repository->saveSignaturePrefs('user_1', [
            'enabled' => true,
            'templateId' => 'classic',
            'overrides' => ['displayName' => 'Primary'],
        ]);
        $this->repository->blockSender('user_1', 'bad@example.com');
        $prefs = $this->repository->signaturePrefs('user_1');
        $this->assertTrue($prefs['enabled']);
        $this->assertSame('Primary', $prefs['overrides']['displayName']);
    }

    public function testBlockedSenderAndPurgedSpamPersistPerMailbox(): void
    {
        $this->repository->blockSender('user_1', 'Spammer <bad@example.com>', 'editor@site.test');
        $this->assertTrue($this->repository->isSenderBlocked('user_1', 'bad@example.com', 'editor@site.test'));
        $this->repository->addPurgedSpamUids('user_1', [7, 8], 'editor@site.test');
        $this->assertTrue($this->repository->isSpamPurged('user_1', 7, 'editor@site.test'));
        $this->assertFalse($this->repository->isSenderBlocked('user_1', 'bad@example.com', ''));
        $this->assertTrue($this->repository->unblockSender('user_1', 'bad@example.com', 'editor@site.test'));
        $this->assertFalse($this->repository->isSenderBlocked('user_1', 'bad@example.com', 'editor@site.test'));
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
