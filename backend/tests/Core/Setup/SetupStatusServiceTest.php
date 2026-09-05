<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Setup;

use PaginiumCMS\Core\Setup\Services\SetupStatusService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class SetupStatusServiceTest extends TestCase
{
    public function testNeedsSetupWhenNoUsersEvenIfInstalledFlagSet(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.installed')->willReturn(true);

        $users = $this->createMock(UserRepository::class);
        $users->method('findAll')->willReturn([]);

        $service = new SetupStatusService($settings, $users);

        $this->assertTrue($service->needsSetup());
        $this->assertTrue($service->isInstalled());
    }

    public function testNeedsSetupWhenFreshInstall(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.installed')->willReturn(false);

        $users = $this->createMock(UserRepository::class);
        $users->method('findAll')->willReturn([]);

        $service = new SetupStatusService($settings, $users);

        $this->assertTrue($service->needsSetup());
        $this->assertFalse($service->isInstalled());
    }

    public function testDoesNotNeedSetupWhenUsersExistAndInstalled(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.installed')->willReturn(true);

        $users = $this->createMock(UserRepository::class);
        $users->method('findAll')->willReturn([new User()]);

        $service = new SetupStatusService($settings, $users);

        $this->assertFalse($service->needsSetup());
        $this->assertTrue($service->isInstalled());
    }

    public function testLegacyInstallWithoutFlagButExistingUsers(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->with('general.installed')->willReturn(false);

        $users = $this->createMock(UserRepository::class);
        $users->method('findAll')->willReturn([new User()]);

        $service = new SetupStatusService($settings, $users);

        $this->assertFalse($service->needsSetup());
        $this->assertTrue($service->isInstalled());
    }
}
