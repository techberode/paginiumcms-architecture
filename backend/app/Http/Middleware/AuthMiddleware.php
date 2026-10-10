<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use PaginiumCMS\Support\JsonHelper;

/**
 * Middleware pre overenie autentifikácie.
 */
class AuthMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const READ_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private AuthenticationInterface $auth,
        private SessionManager $session,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->auth->isAuthenticated()) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Neprihlásený používateľ',
            ]));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        $this->auth->touchSession();

        if ($this->isReadMethod($request->getMethod())) {
            $this->session->releaseWriteLock();
        }

        return $handler->handle($request->withAttribute('user', $this->auth->getCurrentUser()));
    }

    private function isReadMethod(string $method): bool
    {
        return in_array(strtoupper($method), self::READ_METHODS, true);
    }
}
