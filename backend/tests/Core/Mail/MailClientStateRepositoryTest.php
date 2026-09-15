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
