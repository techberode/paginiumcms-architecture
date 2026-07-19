<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\CountsController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(CountsController::class);

    $app->get('/api/admin/counts', [$controller, 'index'])
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['EDITOR', 'ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
