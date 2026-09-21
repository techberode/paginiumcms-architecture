<?php

declare(strict_types=1);

/**
 * Admin teams (It.93k). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/teams
 *  - GET    /api/admin/teams/{id}
 *  - POST   /api/admin/teams          (SUPER_ADMIN only)
 *  - PUT    /api/admin/teams/{id}     (SUPER_ADMIN only)
 *  - DELETE /api/admin/teams/{id}     (SUPER_ADMIN only)
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
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);
    $controller = $container->get(TeamController::class);

    $app->group('/api/admin/teams', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->get('/{id}', [$controller, 'show']);
    })
        ->add(new RoleMiddleware($authz, ['ADMIN', 'SUPER_ADMIN']))
        ->add($twoFactor)
        ->add($auth);

    $app->group('/api/admin/teams', function (RouteCollectorProxy $group) use ($controller) {
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })
        ->add(new RoleMiddleware($authz, ['SUPER_ADMIN']))
        ->add($twoFactor)
        ->add($auth);
};
