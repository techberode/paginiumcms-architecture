<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\MaintenanceMode;
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
        '/api/maintenance/',
        '/api/newsletter/',
        '/api/analytics/pageview',
        '/api/debug/',
        '/api/webhooks/',
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
        $maintenance = $this->settings->group('maintenance');
        if (!MaintenanceMode::isActive($maintenance)) {
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

        $mode = MaintenanceMode::resolve($maintenance);
        $message = $mode === MaintenanceMode::COMING_SOON
            ? 'Stránka sa pripravuje. Skúste to neskôr.'
            : 'Systém je v režime údržby. Skúste to neskôr.';

        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => $message,
            'maintenance' => true,
            'maintenanceMode' => $mode,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus(503)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Retry-After', '300');
    }
}
