<?php

declare(strict_types=1);

/**
 * Admin Performance Guard metrics routes (Iteration 71).
 */

use PaginiumCMS\Http\Controllers\Admin\MetricsController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/metrics/apm', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(MetricsController::class);

        $group->get('', [$controller, 'summary']);
        $group->post('/clear', [$controller, 'clearSamples']);
    })
        ->add(new PermissionMiddleware($authz, 'metrics:read'))
        ->add(new RoleMiddleware($authz, ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
