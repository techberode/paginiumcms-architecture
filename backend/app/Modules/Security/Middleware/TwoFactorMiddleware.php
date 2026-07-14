<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Middleware;

use PaginiumCMS\Modules\Security\Services\TwoFactorManager;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware pre overenie dvojfaktorovej autentifikácie (TOTP 2FA).
 */
class TwoFactorMiddleware implements MiddlewareInterface
{
    private TwoFactorManager $twoFactorManager;

    public function __construct(TwoFactorManager $twoFactorManager)
    {
        $this->twoFactorManager = $twoFactorManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Používateľ nie je prihlásený']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Ak 2FA nie je aktivovaná, pokračujeme
        if (!$user->isTwoFactorEnabled()) {
            return $handler->handle($request);
        }

        // Ak je 2FA už overená v session, pokračujeme
        if ($this->twoFactorManager->isTotpVerified()) {
            return $handler->handle($request);
        }

        // Vyžadujeme TOTP kód
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Vyžaduje sa TOTP overenie',
            'requires_two_factor' => true
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
