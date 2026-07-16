<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class PermissionMiddlewareTest extends TestCase
{
    public function testReturns401WhenUserMissing(): void
    {
        $authz = $this->createMock(AuthorizationInterface::class);
        $middleware = new PermissionMiddleware($authz, 'content:create');

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/pages');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testReturns403WhenPermissionDenied(): void
    {
        $user = new User();
        $user->setRoles(['USER']);

        $authz = $this->createMock(AuthorizationInterface::class);
        $authz->expects($this->once())
            ->method('requirePermission')
            ->with($user, 'content:create')
            ->willThrowException(new AuthorizationException('Zakázané'));

        $middleware = new PermissionMiddleware($authz, 'content:create');
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/pages')
            ->withAttribute('user', $user);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPassesThroughWhenPermissionGranted(): void
    {
        $user = new User();
        $user->setRoles(['EDITOR']);

        $authz = $this->createMock(AuthorizationInterface::class);
        $authz->expects($this->once())->method('requirePermission')->with($user, 'content:edit');

        $middleware = new PermissionMiddleware($authz, 'content:edit');
        $request = (new ServerRequestFactory())
            ->createServerRequest('PUT', '/api/pages/home')
            ->withAttribute('user', $user);

        $expected = (new ResponseFactory())->createResponse(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expected);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }
}
