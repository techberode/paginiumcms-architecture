<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Navigation\NavigationController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(NavigationController::class);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $adminRole = new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']);

    $app->get('/api/navigation', [$controller, 'getNavigation']);
    $app->get('/api/navigation/secondary', [$controller, 'getSecondaryNavigation']);

    $app->group('/api/admin/navigation', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'getAdminNavigation']);
        $group->put('', [$controller, 'updateNavigation']);
        $group->get('/secondary', [$controller, 'getAdminSecondaryNavigation']);
        $group->put('/secondary', [$controller, 'updateSecondaryNavigation']);
    })
        ->add($adminRole)
        ->add($twoFactor)
        ->add($auth);
};
