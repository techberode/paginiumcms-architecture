<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\DemoController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(DemoController::class);

    $app->group('/api/admin/demo', function (RouteCollectorProxy $group) use ($controller): void {
        $group->get('/status', [$controller, 'status']);
        $group->post('/reset', [$controller, 'reset']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
