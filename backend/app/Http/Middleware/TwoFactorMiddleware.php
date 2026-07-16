<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use PaginiumCMS\Support\JsonHelper;

/**
 * === Middleware: TwoFactorMiddleware ===
 * Vynúti TOTP overenie pre chránené routy, ak má používateľ zapnutú 2FA (Iterácia 5).
 *
 * Preskočí cesty v $skipPathPrefixes (napr. /api/auth/me, /api/auth/2fa/*, logout),
 * aby fungoval „polovičný“ login stav a verify-login flow.
 */
class TwoFactorMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    /** @var array<int|string, mixed> */
    private array $skipPathPrefixes = [
        '/api/auth/2fa',
        '/api/auth/me',
        '/api/auth/logout',
        '/api/auth/csrf-token',
    ];

    public function __construct(private TwoFactorInterface $twoFactor)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        foreach ($this->skipPathPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $handler->handle($request);
            }
        }

        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->jsonError('Neprihlásený používateľ', 401);
        }

        if (!$user->isTwoFactorEnabled()) {
            return $handler->handle($request);
        }

        if ($this->twoFactor->isTotpVerified()) {
            return $handler->handle($request);
        }

        return $this->jsonError('Vyžaduje sa TOTP overenie', 401, true);
    }

    private function jsonError(string $message, int $status, bool $requiresTwoFactor = false): ResponseInterface
    {
        $payload = ['success' => false, 'error' => $message];
        if ($requiresTwoFactor) {
            $payload['requires_two_factor'] = true;
        }

        $response = new Response();
        $response->getBody()->write(JsonHelper::encode($payload, JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
