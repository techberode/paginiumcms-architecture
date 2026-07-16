<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Middleware\MaintenanceModeMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class MaintenanceModeMiddlewareTest extends TestCase
{
    public function testPassesWhenMaintenanceDisabled(): void
    {
        $middleware = $this->makeMiddleware(false, false, null);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/pages');

        $expected = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $this->assertSame(200, $middleware->process($request, $handler)->getStatusCode());
    }

    public function testBlocksPublicApiWhenMaintenanceEnabled(): void
    {
        $middleware = $this->makeMiddleware(true, false, null);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/pages');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(503, $response->getStatusCode());
    }

    public function testAllowsHealthDuringMaintenance(): void
    {
        $middleware = $this->makeMiddleware(true, false, null);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/health');

        $expected = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $this->assertSame(200, $middleware->process($request, $handler)->getStatusCode());
    }

    public function testAllowsStaffSessionDuringMaintenance(): void
    {
        $editor = new User();
        $editor->setRoles(['EDITOR']);

        $middleware = $this->makeMiddleware(true, true, $editor);
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/pages');

        $expected = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $this->assertSame(200, $middleware->process($request, $handler)->getStatusCode());
    }

    private function makeMiddleware(bool $maintenance, bool $authenticated, ?User $user): MaintenanceModeMiddleware
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('general')->willReturn(['maintenanceMode' => $maintenance]);

        $auth = $this->createMock(AuthenticationInterface::class);
        $auth->method('isAuthenticated')->willReturn($authenticated);
        $auth->method('getCurrentUser')->willReturn($user);

        $authz = $this->createMock(AuthorizationInterface::class);
        if ($user !== null) {
            $authz->method('hasRole')->willReturn(true);
        }

        return new MaintenanceModeMiddleware($settings, $auth, $authz);
    }
}
