<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/health.php
 * Admin health checks (Iteration 7 wiring).
 */

use PaginiumCMS\Http\Controllers\Admin\HealthController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/health', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(HealthController::class);

        $group->get('', [$controller, 'index']);
        $group->get('/checks', [$controller, 'checks']);
        $group->get('/{name}', [$controller, 'check']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
