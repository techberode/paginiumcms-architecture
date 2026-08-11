<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Gdpr\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Gdpr\GdprPseudonym;
use PaginiumCMS\Core\Gdpr\Services\GdprAnonymizeService;
use PaginiumCMS\Core\Gdpr\Services\GdprExportService;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterRepository;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserAvatarService;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class GdprAnonymizeServiceTest extends TestCase
{
    private string $baseDir;
    private UserRepository $users;
    private CommentsRepository $comments;
    private MessageRepository $messages;
    private NewsletterRepository $newsletter;
    private GdprAnonymizeService $service;
    private GdprExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_gdpr_' . uniqid();
        mkdir($this->baseDir . '/data/users', 0777, true);
        mkdir($this->baseDir . '/data/messages', 0777, true);
        mkdir($this->baseDir . '/data/newsletter', 0777, true);

        $validator = new FileValidator($this->baseDir . '/data');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->users = new UserRepository($reader, $writer, 'users');
        $this->comments = new CommentsRepository($reader, $writer);
        $this->messages = new MessageRepository($reader, $writer);
        $this->newsletter = new NewsletterRepository(
            $reader,
            $writer,
            new NewsletterUnsubscribeToken('test-pepper')
        );

        $this->service = new GdprAnonymizeService(
            $this->users,
            new UserAvatarService($this->createMock(\PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface::class)),
            $this->comments,
            $this->messages,
            $this->newsletter
        );
        $this->exportService = new GdprExportService($this->comments, $this->messages, $this->newsletter);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testAnonymizeRedactsPrimaryStores(): void
    {
        $user = new User();
        $user->setEmail('subject@example.com');
        $user->setUsername('subject');
        $user->setName('Subject Person');
        $user->setPassword('StrongP@ssw0rd123!');
        $user->addRole('USER');
        $this->users->save($user);

        $comment = (new Comment('article-one', 'Subject Person', 'Hello world'))
            ->setEmail('subject@example.com');
        $this->comments->save($comment);

        $message = new ContactMessage('Subject Person', 'subject@example.com', 'Need help');
        $this->messages->save($message);

        $this->newsletter->subscribe('subject@example.com', 'footer');

        $result = $this->service->anonymize($user);

        $this->assertSame(1, $result['commentsUpdated']);
        $this->assertSame(1, $result['contactMessagesUpdated']);
        $this->assertTrue($result['newsletterUpdated']);

        $fresh = $this->users->findById($user->getId());
        $this->assertNotNull($fresh);
        $this->assertTrue(GdprPseudonym::isAnonymizedEmail($fresh->getEmail()));
        $this->assertSame($result['pseudonym'], $fresh->getName());
        $this->assertFalse($fresh->isActive());
        $this->assertNull($this->users->findByEmail('subject@example.com'));

        $export = $this->exportService->buildExport($fresh);
        $this->assertSame([], $export['comments']);
        $this->assertNull($export['newsletter']);
        $this->assertSame([], $export['contactMessages']);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
