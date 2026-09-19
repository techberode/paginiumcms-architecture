<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\PublishedStaffDirectory;
use PaginiumCMS\Modules\Security\Services\UserProfileFields;
use PHPUnit\Framework\TestCase;

final class PublishedStaffDirectoryTest extends TestCase
{
    public function testPresentOmitsPrivateContactUntilPublishFlags(): void
    {
        $user = new User();
        $user->setName('Ada');
        $user->setEmail('ada@example.com');
        $user->setPhone('+421900111222');
        $user->setJobTitle('Support');
        $user->setSocialAccounts(UserProfileFields::normalizeSocialAccounts([
            ['platform' => 'telegram', 'url' => '@adahelp', 'directChat' => true, 'notify' => true, 'verifiedAt' => time()],
        ], true));

        $directory = new PublishedStaffDirectory($this->createStub(\PaginiumCMS\Modules\Security\Services\UserRepository::class));
        $hidden = $directory->present($user);
        $this->assertIsArray($hidden);
        $this->assertArrayNotHasKey('email', $hidden);
        $this->assertArrayNotHasKey('phone', $hidden);
        $this->assertArrayNotHasKey('socials', $hidden);

        $user->setPublish(UserProfileFields::normalizePublish([
            'phone' => true,
            'email' => true,
            'socials' => true,
            'contact' => true,
        ]));
        $card = $directory->present($user);
        $this->assertSame('ada@example.com', $card['email'] ?? null);
        $this->assertSame('+421900111222', $card['phone'] ?? null);
        $this->assertSame('https://t.me/adahelp', $card['socials'][0]['url'] ?? null);
        $this->assertTrue($card['socials'][0]['directChat'] ?? false);
    }

    public function testPresentOmitsUnverifiedSocialLinks(): void
    {
        $user = new User();
        $user->setName('Ada');
        $user->setSocialAccounts(UserProfileFields::normalizeSocialAccounts([
            ['platform' => 'telegram', 'url' => '@hidden', 'directChat' => true],
        ]));
        $user->setPublish(UserProfileFields::normalizePublish(['socials' => true, 'contact' => true]));

        $directory = new PublishedStaffDirectory($this->createStub(\PaginiumCMS\Modules\Security\Services\UserRepository::class));
        $card = $directory->present($user);

        $this->assertIsArray($card);
        $this->assertArrayNotHasKey('socials', $card);
    }

    public function testResolveAndChatRequireListedCard(): void
    {
        $user = new User();
        $user->setName('Ada');
        $user->setEmail('ada-resolve@example.com');
        $user->setChatEnabled(true);
        $user->setPublish(UserProfileFields::normalizePublish(['contact' => true]));

        $users = $this->createStub(\PaginiumCMS\Modules\Security\Services\UserRepository::class);
        $users->method('findById')->willReturnCallback(
            static fn (string $id): ?User => $id === $user->getId() ? $user : null
        );
        $users->method('findByEmail')->willReturnCallback(
            static fn (string $email): ?User => $email === $user->getEmail() ? $user : null
        );
        $users->method('findAll')->willReturn([$user]);

        $directory = new PublishedStaffDirectory($users);
        $cards = $directory->resolveCards($user->getEmail(), null, null);
        $this->assertCount(1, $cards);
        $this->assertTrue($cards[0]['chatEnabled'] ?? false);
        $this->assertTrue($directory->canPublicChat($user));

        $user->setChatEnabled(false);
        $this->assertFalse($directory->canPublicChat($user));
        $hidden = $directory->present($user);
        $this->assertIsArray($hidden);
        $this->assertFalse($hidden['chatEnabled'] ?? true);
    }

    public function testTeamChatFlagGatesPublicChat(): void
    {
        $user = new User();
        $user->setName('Ada');
        $user->setEmail('ada-team@example.com');
        $user->setChatEnabled(true);
        $user->setPublish(UserProfileFields::normalizePublish(['support' => true]));

        $users = $this->createStub(\PaginiumCMS\Modules\Security\Services\UserRepository::class);
        $users->method('findById')->willReturnCallback(
            static fn (string $id): ?User => $id === $user->getId() ? $user : null
        );

        $baseDir = sys_get_temp_dir() . '/pag_staff_dir_' . uniqid('', true);
        mkdir($baseDir . '/data', 0777, true);
        $validator = new \PaginiumCMS\Core\FlatFile\Services\FileValidator($baseDir);
        $teams = new \PaginiumCMS\Core\Teams\Services\TeamRepository(
            new \PaginiumCMS\Core\FlatFile\Services\FileReader($validator),
            new \PaginiumCMS\Core\FlatFile\Services\FileWriter($validator)
        );
        $team = $teams->create('Helpdesk', \PaginiumCMS\Core\Teams\Services\TeamRepository::TYPE_SUPPORT, [$user->getId()]);
        $directory = new PublishedStaffDirectory($users, $teams);

        $this->assertTrue($directory->canPublicChat($user));
        $teams->update((string) $team['id'], ['chatEnabled' => false]);
        $this->assertFalse($directory->canPublicChat($user));

        $this->removeTree($baseDir);
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
