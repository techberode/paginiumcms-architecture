<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Navigation\NavigationController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();
    $controller = $container->get(NavigationController::class);
    $auth = $container->get(AuthMiddleware::class);

    $app->get('/api/navigation', [$controller, 'getNavigation']);

    $app->put('/api/admin/navigation', [$controller, 'updateNavigation'])
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
