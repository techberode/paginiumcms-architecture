<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use PaginiumCMS\Support\JsonHelper;

/**
 * Blokuje verejné API počas režimu údržby; admin/editor session má výnimku.
 */
class MaintenanceModeMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const ALLOWED_PREFIXES = [
        '/api/admin/',
        '/api/auth/',
        '/api/health',
        '/api/test',
        '/api/settings/public',
        '/api/debug/',
        '/storage/',
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private AuthenticationInterface $auth,
        private AuthorizationInterface $authz
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $general = $this->settings->group('general');
        if (($general['maintenanceMode'] ?? false) !== true) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        if ($path === '/' || $path === '/favicon.ico') {
            return $handler->handle($request);
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $handler->handle($request);
            }
        }

        if ($this->auth->isAuthenticated()) {
            $user = $this->auth->getCurrentUser();
            if ($user !== null && $this->authz->hasRole($user, ['EDITOR', 'ADMIN', 'SUPER_ADMIN'])) {
                return $handler->handle($request);
            }
        }

        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => 'Systém je v režime údržby. Skúste to neskôr.',
            'maintenance' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus(503)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Retry-After', '300');
    }
}
