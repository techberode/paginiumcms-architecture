<?php

declare(strict_types=1);

/**
 * Coming-soon countdown linked to a page/article (It.93v).
 *
 *  - GET    /api/coming-soon/{kind}/{slug}
 *  - GET    /api/admin/coming-soon
 *  - GET    /api/admin/coming-soon/targets
 *  - POST   /api/admin/coming-soon
 *  - PUT    /api/admin/coming-soon/{id}
 *  - DELETE /api/admin/coming-soon/{id}
 */

use PaginiumCMS\Http\Controllers\Admin\ComingSoonController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(ComingSoonController::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->get('/api/coming-soon/{kind}/{slug}', [$controller, 'showPublic']);

    $app->group('/api/admin/coming-soon', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'index']);
        $group->get('/targets', [$controller, 'targets']);
        $group->post('', [$controller, 'store']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })
        ->add(new PermissionMiddleware($authz, 'time-entry:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
