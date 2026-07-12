<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

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
    private TwoFactorManager $twoFactor;

    public function __construct(TwoFactorManager $twoFactor)
    {
        $this->twoFactor = $twoFactor;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Neprihlásený používateľ',
            ]));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        // Ak 2FA nie je aktivovaná, pokračujeme
        if (!$user->isTwoFactorEnabled()) {
            return $handler->handle($request);
        }

        // Ak je 2FA už overená v session, pokračujeme
        if ($this->twoFactor->isTotpVerified()) {
            return $handler->handle($request);
        }

        // Vyžadujeme TOTP kód
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Vyžaduje sa TOTP overenie',
            'requires_two_factor' => true,
        ]));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
