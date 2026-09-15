<?php

declare(strict_types=1);

/**
 * Admin teams (It.93k). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/teams
 *  - GET    /api/admin/teams/{id}
 *  - POST   /api/admin/teams
 *  - PUT    /api/admin/teams/{id}
 *  - DELETE /api/admin/teams/{id}
 */

use PaginiumCMS\Http\Controllers\Admin\TeamController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/teams', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(TeamController::class);

        $group->get('', [$controller, 'index']);
        $group->get('/{id}', [$controller, 'show']);
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
