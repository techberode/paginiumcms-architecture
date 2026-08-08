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
 * Rejects malformed or invalid managed Bearer tokens without session fallback (It.74).
 */
final class InvalidBearerGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ApiBearerAuthenticator $authenticator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine('Authorization');
        if ($authorization === '' || !$this->authenticator->looksLikeManagedBearer($authorization)) {
            return $handler->handle($request);
        }

        $context = $this->authenticator->resolve($authorization);
        if ($context === null) {
            return $this->unauthorized('Invalid or expired bearer credential');
        }

        return $handler->handle($request->withAttribute('api_bearer', $context));
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => $message,
        ]));

        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
