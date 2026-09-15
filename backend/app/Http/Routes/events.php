<?php

declare(strict_types=1);

/**
 * Admin site events (It.93n). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/events
 *  - GET    /api/admin/events/{id}
 *  - POST   /api/admin/events
 *  - PUT    /api/admin/events/{id}
 *  - DELETE /api/admin/events/{id}
 */

use PaginiumCMS\Http\Controllers\Admin\EventController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/events', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(EventController::class);

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
