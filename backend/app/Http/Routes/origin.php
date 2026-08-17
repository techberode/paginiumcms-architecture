<?php

declare(strict_types=1);

/**
 * Maintainer-only Origin Panel API (It.82). Auto-discovered from bootstrap/app.php.
 *
 *  - GET /api/admin/origin/overview
 *  - GET /api/admin/origin/probes
 */

use PaginiumCMS\Http\Controllers\Origin\OriginController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/origin', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(OriginController::class);

        $group->get('/overview', [$controller, 'overview']);
        $group->get('/probes', [$controller, 'probes']);
        $group->get('/catalog', [$controller, 'catalog']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
