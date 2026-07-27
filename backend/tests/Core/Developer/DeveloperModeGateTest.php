<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Developer;

use PaginiumCMS\Core\Developer\DevTokenGenerator;
use PaginiumCMS\Core\Developer\DevTokenRegistry;
use PaginiumCMS\Core\Developer\DeveloperModeGate;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\TwoFactorManager;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Tests\Http\TestCase;

final class DeveloperModeGateTest extends TestCase
{
    /** @var array{env: string|false, server: mixed} */
    private array $savedAppEnv = ['env' => false, 'server' => null];

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedAppEnv = [
            'env' => getenv('APP_ENV'),
            'server' => $_ENV['APP_ENV'] ?? null,
        ];
        putenv('APP_DEBUG=true');
        $_ENV['APP_DEBUG'] = 'true';
    }

    protected function tearDown(): void
    {
        if ($this->savedAppEnv['env'] === false) {
            putenv('APP_ENV');
            unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        } else {
            putenv('APP_ENV=' . $this->savedAppEnv['env']);
            $_ENV['APP_ENV'] = $this->savedAppEnv['server'];
            $_SERVER['APP_ENV'] = $this->savedAppEnv['server'];
        }

        parent::tearDown();
    }

    public function testUnlockWithTotpUsesFreshUserFromStorageWhenSessionIsStale(): void
    {
        $login = $this->loginAsAdminUser();
        $container = $this->app->getContainer();

        /** @var UserRepository $repo */
        $repo = $container->get(UserRepository::class);
        /** @var TwoFactorManager $twoFactor */
        $twoFactor = $container->get(TwoFactorInterface::class);
        /** @var DeveloperModeGate $gate */
        $gate = $container->get(DeveloperModeGate::class);

        $dbUser = $repo->findByEmail($login['email']);
        $this->assertNotNull($dbUser);

        $secret = $twoFactor->enableTwoFactor($dbUser);
        $code = $twoFactor->getCurrentCode($secret);

        // Simulácia zastaranej session – 2FA v DB zapnutá, v session ešte nie.
        $staleUser = $repo->findByEmail($login['email']);
        $this->assertNotNull($staleUser);
        $staleUser->setTwoFactorEnabled(false);
        $this->setCurrentUser($staleUser);

        $this->assertTrue($gate->unlockWithTotp($staleUser, $code, $twoFactor));
        $this->assertTrue($gate->isUnlocked());
    }

    public function testFeatureAvailableWhenAppEnvIsDevelopmentWithoutDeveloperModeFlag(): void
    {
        putenv('DEVELOPER_MODE');
        unset($_ENV['DEVELOPER_MODE']);
        putenv('APP_DEBUG');
        unset($_ENV['APP_DEBUG']);
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';

        $gate = $this->app->getContainer()->get(DeveloperModeGate::class);

        $this->assertTrue($gate->isFeatureAvailable());
    }

    public function testFeatureUnavailableWhenProductionWithoutFlags(): void
    {
        putenv('DEVELOPER_MODE');
        unset($_ENV['DEVELOPER_MODE']);
        putenv('APP_DEBUG=false');
        $_ENV['APP_DEBUG'] = 'false';
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        $gate = $this->app->getContainer()->get(DeveloperModeGate::class);

        $this->assertFalse($gate->isFeatureAvailable());
    }
}
