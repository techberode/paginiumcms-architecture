<?php

declare(strict_types=1);

/**
 * Built-in public widgets catalog (It.93t). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/widgets
 *  - GET    /api/admin/widgets/{name}
 *  - PUT    /api/admin/widgets/{name}
 *  - POST   /api/admin/widgets/preview
 *  - DELETE /api/admin/widgets/{name}
 */

use PaginiumCMS\Http\Controllers\Admin\WidgetController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/widgets', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(WidgetController::class);

        $group->get('', [$controller, 'index']);
        $group->post('/preview', [$controller, 'preview']);
    })->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));

    $app->group('/api/admin/widgets', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(WidgetController::class);

        $group->get('/{name}', [$controller, 'show']);
        $group->put('/{name}', [$controller, 'save']);
        $group->delete('/{name}', [$controller, 'delete']);
    })->add(new PermissionMiddleware($authz, 'settings:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
