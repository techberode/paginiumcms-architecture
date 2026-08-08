<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Models\ApiBearerAuth;
use PaginiumCMS\Modules\Security\Services\ApiBearerAuthenticator;
use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Requires a valid Bearer API key or JWT on headless routes (It.74).
 */
final class BearerAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ApiBearerAuthenticator $authenticator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $existing = $request->getAttribute('api_bearer');
        if ($existing instanceof ApiBearerAuth) {
            return $handler->handle($request);
        }

        $authorization = $request->getHeaderLine('Authorization');
        $context = $this->authenticator->resolve($authorization);
        if ($context === null) {
            return $this->unauthorized();
        }

        return $handler->handle($request->withAttribute('api_bearer', $context));
    }

    private function unauthorized(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => 'Valid bearer credential required',
        ]));

        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
