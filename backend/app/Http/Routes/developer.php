<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\DeveloperController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\DeveloperModeMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();

    $app->group('/api/admin/developer', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(DeveloperController::class);

        // Status a unlock nevyžadujú odomknutý dev mód (unlock je vstupný bod)
        $group->get('/status', [$controller, 'status']);
        $group->post('/unlock', [$controller, 'unlock']);
        $group->post('/lock', [$controller, 'lock']);

        // Logy len po odomknutí
        $group->get('/logs', [$controller, 'logs'])
            ->add($container->get(DeveloperModeMiddleware::class));
    })
        ->add(new RoleMiddleware(
            $container->get(AuthorizationInterface::class),
            ['ADMIN', 'SUPER_ADMIN']
        ))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
