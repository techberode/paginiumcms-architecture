<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Middleware;

use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware pre overenie autorizácie (RBAC).
 */
class AuthorizationMiddleware implements MiddlewareInterface
{
    private AuthorizationManager $authzManager;
    private array $requiredRoles;

    public function __construct(AuthorizationManager $authzManager, array $requiredRoles)
    {
        $this->authzManager = $authzManager;
        $this->requiredRoles = $requiredRoles;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Používateľ nie je prihlásený']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        try {
            $this->authzManager->requireRole($user, $this->requiredRoles);
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
