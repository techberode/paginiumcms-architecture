<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\RegistrationInvitesController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/registration-invites', function (RouteCollectorProxy $group) use ($container): void {
        $controller = $container->get(RegistrationInvitesController::class);
        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'store']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
