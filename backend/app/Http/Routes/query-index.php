<?php

declare(strict_types=1);

/**
 * Admin derived query index (It.92).
 *
 * GET  /api/admin/query-index/status
 * POST /api/admin/query-index/rebuild
 * POST /api/admin/query-index/activate  body: { "driver": "json"|"sqlite" }
 */

use PaginiumCMS\Http\Controllers\Admin\QueryIndexController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/query-index', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(QueryIndexController::class);

        $group->get('/status', [$controller, 'status']);
        $group->post('/rebuild', [$controller, 'rebuild']);
        $group->post('/activate', [$controller, 'activate']);
    })
        ->add(new PermissionMiddleware($authz, 'settings:manage'))
        ->add(new RoleMiddleware($authz, ['SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
