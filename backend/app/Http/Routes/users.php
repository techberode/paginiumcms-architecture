<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/users.php
 * Admin správa používateľov (Iterácia 5). Auto-discovered z bootstrap/app.php.
 *
 *  - GET    /api/admin/users
 *  - GET    /api/admin/users/{id}
 *  - POST   /api/admin/users
 *  - PUT    /api/admin/users/{id}
 *  - DELETE /api/admin/users/{id}
 */

use PaginiumCMS\Http\Controllers\Admin\UserController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/users', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(UserController::class);

        $group->get('', [$controller, 'index']);
        $group->post('/bulk-delete', [$controller, 'bulkDestroy']);
        $group->get('/{id}', [$controller, 'show']);
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
        $group->post('/{id}/avatar', [$controller, 'uploadAvatar']);
        $group->delete('/{id}/avatar', [$controller, 'removeAvatar']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
