<?php

declare(strict_types=1);

/**
 * Admin cache management routes.
 *
 * GET  /api/admin/cache        – cache stats
 * POST /api/admin/cache/purge  – manual purge (body: { "scope": "content"|"all" })
 */

use PaginiumCMS\Http\Controllers\Admin\CacheController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/cache', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(CacheController::class);

        $group->get('', [$controller, 'status']);
        $group->post('/purge', [$controller, 'purge']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
