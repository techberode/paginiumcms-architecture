<?php

declare(strict_types=1);

/**
 * Derived static-tree compile API (It.48). Auto-discovered from bootstrap/app.php.
 *
 *  - GET  /api/admin/static/status
 *  - POST /api/admin/static/rebuild
 */

use PaginiumCMS\Http\Controllers\Admin\StaticSiteController;
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

    $app->group('/api/admin/static', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(StaticSiteController::class);

        $group->get('/status', [$controller, 'status']);
        $group->post('/rebuild', [$controller, 'rebuild']);
    })->add(new PermissionMiddleware($authz, 'static:rebuild'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
