<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserProfileFields;
use PHPUnit\Framework\TestCase;

final class UserProfileFieldsTest extends TestCase
{
    public function testApplyStoresOptionalProfileAndPrefFields(): void
    {
        $user = new User();
        $user->setEmail('ada@example.com');
        $user->setName('Ada');

        UserProfileFields::apply($user, [
            'jobTitle' => 'Editor',
            'phone' => '+421 900 111 222',
            'timezone' => 'Europe/Bratislava',
            'locale' => 'en',
            'bio' => "Line\none",
            'notifyFailedLogin' => false,
            'notifySecurityIncident' => 'false',
        ]);

        $this->assertSame('Editor', $user->getJobTitle());
        $this->assertSame('+421 900 111 222', $user->getPhone());
        $this->assertSame('Europe/Bratislava', $user->getTimezone());
        $this->assertSame('en', $user->getLocale());
        $this->assertSame('Line one', $user->getBio());
        $this->assertFalse($user->getNotifyFailedLogin());
        $this->assertFalse($user->getNotifySecurityIncident());
    }

    public function testApplyPublicProfileListsAndPublishFlags(): void
    {
        $user = new User();
        $user->setEmail('ada@example.com');
        $user->setName('Ada');

        UserProfileFields::apply($user, [
            'address' => ['street' => "Main\nSt", 'city' => 'Bratislava', 'postal' => '81101', 'country' => 'SK'],
            'experience' => [['org' => 'Webland', 'role' => 'Editor', 'years' => '2020—']],
            'education' => [['school' => 'STU', 'field' => 'IT', 'years' => '2016-2020']],
            'socialAccounts' => [
                ['platform' => 'telegram', 'url' => '@desk', 'directChat' => true, 'notify' => true],
            ],
            'publish' => ['contact' => true, 'phone' => true],
        ]);

        $this->assertSame('Main St', $user->getAddress()['street']);
        $this->assertSame('Webland', $user->getExperience()[0]['org'] ?? null);
        $this->assertSame('STU', $user->getEducation()[0]['school'] ?? null);
        $this->assertSame('telegram', $user->getSocialAccounts()[0]['platform'] ?? null);
        $this->assertTrue($user->getPublish()['contact']);
        $this->assertFalse($user->getPublish()['email']);
        $this->assertSame('https://t.me/desk', UserProfileFields::chatUrl('telegram', '@desk'));
    }

    public function testEmptyTimezoneAndLocaleInheritSite(): void
    {
        $user = new User();
        $user->setTimezone('UTC');
        $user->setLocale('en');

        UserProfileFields::apply($user, [
            'timezone' => '',
            'locale' => '',
        ]);

        $this->assertSame('', $user->getTimezone());
        $this->assertSame('', $user->getLocale());
    }

    public function testRejectsInvalidPhone(): void
    {
        $this->expectException(ValidationException::class);
        UserProfileFields::normalizePhone('drop table');
    }
}
