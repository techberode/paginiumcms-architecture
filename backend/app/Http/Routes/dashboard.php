<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/dashboard.php
 * Admin dashboard overview (Iteration 7).
 */

use PaginiumCMS\Http\Controllers\Admin\DashboardController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/dashboard', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(DashboardController::class);

        $group->get('/overview', [$controller, 'overview']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
