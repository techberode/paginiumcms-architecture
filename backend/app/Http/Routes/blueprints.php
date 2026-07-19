<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\BlueprintController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/blueprints', function (RouteCollectorProxy $group) use ($container): void {
        $controller = $container->get(BlueprintController::class);

        $group->get('', [$controller, 'index']);
        $group->get('/{type}', [$controller, 'show']);
        $group->put('/{type}', [$controller, 'update']);
        $group->post('/{type}/validate', [$controller, 'validatePayload']);
        $group->delete('/{type}', [$controller, 'destroy']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
