<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Messages\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Messages\Services\ReplyMailboxResolver;
use PaginiumCMS\Modules\Messages\Services\VisitorReplyMailer;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

final class VisitorReplyMailerTest extends TestCase
{
    private string $baseDir;
    private TeamRepository $teams;
    private ReplyMailboxResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_visitor_mail_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->teams = new TeamRepository(new FileReader($validator), new FileWriter($validator));
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['siteUrl' => 'https://cms.example.com']);
        $this->resolver = new ReplyMailboxResolver($settings, $this->teams);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testSendsFromOperatorMailboxToVisitor(): void
    {
        $probe = new class implements AdapterInterface {
            public string $to = '';
            public string $from = '';
            public string $fromName = '';
            /** @var array<int|string, mixed> */
            public array $options = [];

            public function send(string $to, string $subject, string $message, array $options = []): bool
            {
                $this->to = $to;
                $this->from = (string) ($options['from'] ?? '');
                $this->fromName = (string) ($options['from_name'] ?? '');
                $this->options = $options;

                return true;
            }
        };

        $notifications = new NotificationService();
        $notifications->addAdapter('email', $probe);
        $mailer = new VisitorReplyMailer($this->resolver, $notifications);

        $actor = new User();
        $actor->setName('Ada');
        $actor->setEmail('ada@cms.example.com');
        $actor->setDeskMailEnabled(true);

        $this->assertTrue($mailer->send('guest@example.com', $actor, 'Re: Help', 'We are looking into it.'));
        $this->assertSame('guest@example.com', $probe->to);
        $this->assertSame('', $probe->from);
        $this->assertSame('ada@cms.example.com', (string) ($probe->options['reply_to'] ?? ''));
    }

    public function testSkipsWhenNoDomainFrom(): void
    {
        $mailer = new VisitorReplyMailer($this->resolver, new NotificationService());

        $actor = new User();
        $actor->setEmail('ada@gmail.com');
        $actor->setDeskMailEnabled(true);

        $this->assertFalse($mailer->send('guest@example.com', $actor, 'Re: Help', 'Body'));
    }

    public function testSendsToVisitorUsingSmtpFromWhenNoSiteMailbox(): void
    {
        $probe = new class implements AdapterInterface {
            public string $to = '';
            /** @var array<int|string, mixed> */
            public array $options = [];

            public function send(string $to, string $subject, string $message, array $options = []): bool
            {
                $this->to = $to;
                $this->options = $options;

                return true;
            }
        };

        $notifications = new NotificationService();
        $notifications->addAdapter('email', $probe);
        $mailer = new VisitorReplyMailer($this->resolver, $notifications);

        $actor = new User();
        $actor->setEmail('ada@gmail.com');
        $actor->setDeskMailEnabled(true);

        $this->assertTrue($mailer->send('guest@example.com', $actor, 'Re: Help', 'We will call you.'));
        $this->assertSame('guest@example.com', $probe->to);
        $this->assertArrayNotHasKey('from', $probe->options);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($path);
    }
}
