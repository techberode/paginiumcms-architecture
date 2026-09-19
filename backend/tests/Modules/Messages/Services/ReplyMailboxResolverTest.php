<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Messages\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\ReplyMailboxResolver;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

final class ReplyMailboxResolverTest extends TestCase
{
    private string $baseDir;
    private TeamRepository $teams;
    private ReplyMailboxResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_reply_mail_' . uniqid('', true);
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

    public function testPrefersOperatorDomainMailbox(): void
    {
        $actor = new User();
        $actor->setName('Ada');
        $actor->setEmail('ada@cms.example.com');
        $actor->setDeskMailEnabled(true);

        $from = $this->resolver->resolve($actor);
        $this->assertNotNull($from);
        $this->assertSame('ada@cms.example.com', $from['email']);
    }

    public function testFallsBackToTeamMailbox(): void
    {
        $actor = new User();
        $actor->setEmail('ada@gmail.com');
        $actor->setDeskMailEnabled(true);
        $team = $this->teams->create('Support', TeamRepository::TYPE_SUPPORT, [$actor->getId()]);
        $this->teams->update((string) $team['id'], [
            'replyMailEnabled' => true,
            'replyMail' => 'support@cms.example.com',
        ]);

        $from = $this->resolver->resolve($actor);
        $this->assertNotNull($from);
        $this->assertSame('support@cms.example.com', $from['email']);
    }

    public function testRejectsPublicProviderFrom(): void
    {
        $actor = new User();
        $actor->setEmail('ada@gmail.com');
        $actor->setDeskMailEnabled(true);

        $this->assertNull($this->resolver->resolve($actor, new ContactMessage('V', 'v@example.com', 'Hello there.')));
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
