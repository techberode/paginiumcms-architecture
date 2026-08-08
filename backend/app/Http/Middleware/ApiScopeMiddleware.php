<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Models\ApiBearerAuth;
use PaginiumCMS\Modules\Security\Services\ApiScopePolicy;
use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Enforces route allow-list scopes for authenticated bearer principals (It.74).
 */
final class ApiScopeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ApiScopePolicy $policy,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = $request->getAttribute('api_bearer');
        if (!$context instanceof ApiBearerAuth) {
            return $this->forbidden('Bearer context missing');
        }

        $path = $request->getUri()->getPath();
        if (!$this->policy->allows($request->getMethod(), $path, $context->scopes)) {
            return $this->forbidden('Insufficient scope for this route');
        }

        return $handler->handle($request);
    }

    private function forbidden(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => $message,
        ]));

        return $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'application/json');
    }
}
