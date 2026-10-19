<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Setup;

use PaginiumCMS\Core\Setup\Services\SetupAccessGuard;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SetupAccessGuardTest extends TestCase
{
    private string|false $savedEnv;
    private string|false $savedToken;

    protected function setUp(): void
    {
        $this->savedEnv = getenv('APP_ENV');
        $this->savedToken = getenv('SETUP_TOKEN');
        putenv('SETUP_TOKEN');
    }

    protected function tearDown(): void
    {
        putenv($this->savedEnv === false ? 'APP_ENV' : 'APP_ENV=' . $this->savedEnv);
        putenv($this->savedToken === false ? 'SETUP_TOKEN' : 'SETUP_TOKEN=' . $this->savedToken);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonDevelopmentEnvs(): iterable
    {
        yield 'production' => ['production'];
        yield 'staging' => ['staging'];
        yield 'typo prod' => ['prod'];
        yield 'live' => ['live'];
        yield 'uppercase' => ['PRODUCTION'];
    }

    /**
     * @dataProvider nonDevelopmentEnvs
     */
    public function testMissingTokenFailsClosedOutsideDevelopment(string $env): void
    {
        putenv('APP_ENV=' . $env);
        $guard = new SetupAccessGuard();

        $this->assertTrue($guard->tokenRequired());
        $this->assertFalse($guard->verifySetupToken($this->request()));
    }

    public function testUnsetAppEnvIsTreatedAsProduction(): void
    {
        putenv('APP_ENV');

        $this->assertFalse((new SetupAccessGuard())->verifySetupToken($this->request()));
    }

    public function testDevelopmentAllowsSetupWithoutToken(): void
    {
        putenv('APP_ENV=development');

        $this->assertTrue((new SetupAccessGuard())->verifySetupToken($this->request()));
    }

    public function testConfiguredTokenMustMatch(): void
    {
        putenv('APP_ENV=development');
        putenv('SETUP_TOKEN=s3cret-token');
        $guard = new SetupAccessGuard();

        $this->assertFalse($guard->verifySetupToken($this->request()));
        $this->assertFalse($guard->verifySetupToken($this->request('wrong')));
        $this->assertTrue($guard->verifySetupToken($this->request('s3cret-token')));
        $this->assertTrue($guard->verifySetupToken($this->request(), 's3cret-token'));
    }

    private function request(?string $token = null): \Psr\Http\Message\ServerRequestInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/setup/complete');

        return $token === null ? $request : $request->withHeader('X-Setup-Token', $token);
    }
}
