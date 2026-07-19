<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security;

use PaginiumCMS\Core\Security\TwoFactorPolicy;
use PHPUnit\Framework\TestCase;

final class TwoFactorPolicyTest extends TestCase
{
    private ?string $previousRequired = null;
    private ?string $previousEnv = null;

    protected function setUp(): void
    {
        $this->previousRequired = getenv('TWO_FACTOR_REQUIRED') ?: null;
        $this->previousEnv = getenv('APP_ENV') ?: null;
    }

    protected function tearDown(): void
    {
        $this->restoreEnv('TWO_FACTOR_REQUIRED', $this->previousRequired);
        $this->restoreEnv('APP_ENV', $this->previousEnv);
    }

    public function testRequiredByDefault(): void
    {
        putenv('TWO_FACTOR_REQUIRED');
        unset($_ENV['TWO_FACTOR_REQUIRED']);
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';

        $this->assertTrue(TwoFactorPolicy::isRequired());
    }

    public function testCanDisableInDevelopment(): void
    {
        putenv('TWO_FACTOR_REQUIRED=false');
        $_ENV['TWO_FACTOR_REQUIRED'] = 'false';
        putenv('APP_ENV=development');
        $_ENV['APP_ENV'] = 'development';

        $this->assertFalse(TwoFactorPolicy::isRequired());
    }

    public function testCannotDisableOnProduction(): void
    {
        putenv('TWO_FACTOR_REQUIRED=false');
        $_ENV['TWO_FACTOR_REQUIRED'] = 'false';
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        $this->assertTrue(TwoFactorPolicy::isRequired());
    }

    private function restoreEnv(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            putenv($key);
            unset($_ENV[$key]);
            return;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}
