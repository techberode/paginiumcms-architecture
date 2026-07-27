<?php

declare(strict_types=1);

/**
 * Admin system update routes (Iteration 63).
 */

use PaginiumCMS\Http\Controllers\Admin\SystemUpdateController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/system/update', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(SystemUpdateController::class);

        $group->get('/status', [$controller, 'status']);
        $group->post('/check', [$controller, 'check']);
        $group->post('/run', [$controller, 'run']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
