<?php

declare(strict_types=1);

/**
 * Component playground (It.95). SUPER_ADMIN only.
 *
 *  - GET /api/admin/playground
 *  - GET /api/admin/playground/assets/{packId}/{path:.*}
 *  - POST /api/admin/playground/import-git
 */

use PaginiumCMS\Http\Controllers\Admin\PlaygroundController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/playground', function (RouteCollectorProxy $group) use ($container): void {
        $controller = $container->get(PlaygroundController::class);
        $group->get('', [$controller, 'show']);
        $group->get('/assets/{packId}/{path:.*}', [$controller, 'asset']);
        $group->post('/import-git', [$controller, 'importGit']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
