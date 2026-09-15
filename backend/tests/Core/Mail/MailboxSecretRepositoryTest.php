<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Mail\Services\MailboxSecretRepository;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PHPUnit\Framework\TestCase;

final class MailboxSecretRepositoryTest extends TestCase
{
    private string $baseDir;
    private MailboxSecretRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_mailsec_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->repository = new MailboxSecretRepository(
            new FileReader($validator),
            new FileWriter($validator),
            new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=')
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testEncryptsPasswordAtRest(): void
    {
        $this->repository->savePassword('user_abc', 's3cret');
        $this->assertSame('s3cret', $this->repository->getPassword('user_abc'));

        $files = glob($this->baseDir . '/data/mail-secrets/*.json');
        $this->assertNotFalse($files);
        $this->assertNotEmpty($files);
        $raw = file_get_contents($files[0]);
        $this->assertNotFalse($raw);
        $this->assertStringNotContainsString('s3cret', $raw);

        $this->repository->delete('user_abc');
        $this->assertNull($this->repository->getPassword('user_abc'));
    }

    public function testStoresExtraMailboxAndSwitchesActive(): void
    {
        $this->repository->savePassword('user_abc', 'primary-pass', 'editor@paginium.test');
        $this->repository->addAccount('user_abc', 'info@paginium.test', 'info-pass', 'editor@paginium.test');

        $accounts = $this->repository->listAccounts('user_abc', 'editor@paginium.test');
        $this->assertCount(2, $accounts);
        $this->assertSame('info@paginium.test', $this->repository->activeMailbox('user_abc', 'editor@paginium.test'));
        $this->assertSame('info-pass', $this->repository->getPassword('user_abc', 'info@paginium.test'));
        $this->assertSame('primary-pass', $this->repository->getPassword('user_abc', 'editor@paginium.test'));

        $this->repository->setActive('user_abc', 'editor@paginium.test', 'editor@paginium.test');
        $this->assertSame('editor@paginium.test', $this->repository->activeMailbox('user_abc', 'editor@paginium.test'));

        $this->repository->removeAccount('user_abc', 'info@paginium.test', 'editor@paginium.test');
        $this->assertCount(1, $this->repository->listAccounts('user_abc', 'editor@paginium.test'));
        $this->assertNull($this->repository->getPassword('user_abc', 'info@paginium.test'));
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
