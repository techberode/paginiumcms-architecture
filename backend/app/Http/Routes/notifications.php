<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/notifications.php
 * Admin notification overview and test-send (Iteration 6).
 */

use PaginiumCMS\Http\Controllers\Admin\NotificationController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/notifications', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(NotificationController::class);

        $group->get('/overview', [$controller, 'overview']);
        $group->post('/test', [$controller, 'testSend']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
