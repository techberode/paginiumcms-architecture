<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Developer\DeveloperModeGate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use PaginiumCMS\Support\JsonHelper;

/**
 * Vyžaduje odomknutý Developer Mode pre code-editor a dev API.
 * Beží až za AuthMiddleware + RoleMiddleware (admin).
 */
class DeveloperModeMiddleware implements MiddlewareInterface
{
    public function __construct(private DeveloperModeGate $gate)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->gate->isFeatureAvailable()) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Developer Mode nie je povolený v konfigurácii (DEVELOPER_MODE / APP_DEBUG)',
            ], JSON_UNESCAPED_UNICODE));

            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        if (!$this->gate->isUnlocked()) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Developer Mode je zamknutý. Odomknite cez TOTP alebo dev token.',
                'gate' => $this->gate->getStatus(),
            ], JSON_UNESCAPED_UNICODE));

            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
