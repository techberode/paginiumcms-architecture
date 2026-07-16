<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Middleware;

use PaginiumCMS\Modules\Security\Services\AuthenticationManager;
use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware pre overenie autentifikácie.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    private AuthenticationManager $authManager;

    public function __construct(AuthenticationManager $authManager)
    {
        $this->authManager = $authManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authManager->isAuthenticated()) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode(['error' => 'Neprihlásený používateľ']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request->withAttribute('user', $this->authManager->getCurrentUser()));
    }
}
