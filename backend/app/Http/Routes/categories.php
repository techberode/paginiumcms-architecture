<?php

declare(strict_types=1);

/**
 * Content category taxonomy API (It.84a).
 */

use PaginiumCMS\Http\Controllers\Content\CategoriesController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(CategoriesController::class);

    $app->get('/api/categories', [$controller, 'publicIndex']);

    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/categories', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'store']);
        $group->post('/bulk-delete', [$controller, 'bulkDelete']);
        $group->put('/{slug}', [$controller, 'update']);
        $group->delete('/{slug}', [$controller, 'delete']);
    })
        ->add(new PermissionMiddleware($authz, 'settings:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
