<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Teams\Services\TeamRepository;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\RegistrationOptionsStore;
use PaginiumCMS\Modules\Security\Services\RegistrationService;
use PaginiumCMS\Modules\Security\Services\RoleRepository;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class RegistrationServiceTest extends TestCase
{
    private string $baseDir;
    private RegistrationService $service;
    private RegistrationOptionsStore $options;
    private TeamRepository $teams;
    private UserRepository $users;
    private RegistrationMailProbe $mailProbe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_regsvc_' . uniqid('', true);
        mkdir($this->baseDir . '/data/users', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $roles = new RoleRepository($reader, $writer);
        $roles->save('USER', 'User', ['profile:edit'], true);
        $this->options = new RegistrationOptionsStore($reader, $writer, $roles);
        $this->teams = new TeamRepository($reader, $writer);
        $this->users = new UserRepository($reader, $writer);
        $this->mailProbe = new RegistrationMailProbe();
        $notifications = new NotificationService();
        $notifications->addAdapter('email', $this->mailProbe);
        $this->service = new RegistrationService($this->options, $this->teams, $this->users, $notifications);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testPlanDefaultsWhenNoTypes(): void
    {
        $plan = $this->service->plan(null);
        $this->assertSame(['USER'], $plan['roles']);
        $this->assertTrue($plan['active']);
    }

    public function testPlanRequiresTypeWhenOptionsExist(): void
    {
        $this->options->save([[
            'label' => 'Developer',
            'roleId' => 'USER',
            'enabled' => true,
            'requireAdminApproval' => true,
        ]]);
        $this->expectException(InvalidArgumentException::class);
        $this->service->plan(null);
    }

    public function testApproveMailsWithoutAutoTeam(): void
    {
        $team = $this->teams->create('Devs', TeamRepository::TYPE_EXTERNAL, []);
        $saved = $this->options->save([[
            'label' => 'Developer',
            'roleId' => 'USER',
            'enabled' => true,
            'requireAdminApproval' => true,
            'assignTeamId' => $team['id'],
            'welcomeMailEnabled' => true,
            'welcomeMailSubject' => 'You are in',
            'welcomeMailBody' => 'Welcome.',
        ]]);
        $user = new User();
        $user->setEmail('dev@example.com');
        $user->setName('Dev');
        $user->setPassword('StrongP@ssw0rd123!');
        $user->setActive(false);
        $user->setRegistrationOptionId((string) $saved[0]['id']);
        $this->users->save($user);

        $this->service->approve($user);
        $this->assertTrue($user->isActive());
        $fresh = $this->teams->get((string) $team['id']);
        $this->assertNotContains($user->getId(), $fresh['memberUserIds'] ?? []);
        $this->assertSame('dev@example.com', $this->mailProbe->to);
        $this->assertSame('You are in', $this->mailProbe->subject);
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

final class RegistrationMailProbe implements AdapterInterface
{
    public string $to = '';
    public string $subject = '';

    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        $this->to = $to;
        $this->subject = $subject;

        return true;
    }
}
