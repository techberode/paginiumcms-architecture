<?php

declare(strict_types=1);

/**
 * Custom RBAC roles API (It.84d) — SUPER_ADMIN only.
 */

use PaginiumCMS\Http\Controllers\Admin\RolesController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(RolesController::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/roles', function (RouteCollectorProxy $group) use ($controller): void {
        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'store']);
        $group->post('/bulk-delete', [$controller, 'bulkDelete']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'delete']);
    })
        ->add(new RoleMiddleware($authz, ['SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
