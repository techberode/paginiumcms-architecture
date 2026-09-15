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
            ['platform' => 'telegram', 'url' => '@adahelp', 'directChat' => true, 'notify' => true],
        ]));

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
}
