<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\FormController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $authz = $container->get(AuthorizationInterface::class);
    $controller = $container->get(FormController::class);

    $app->group('/api/admin/forms', function (RouteCollectorProxy $group) use ($controller): void {
        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'store']);
        $group->post('/presets', [$controller, 'createFromPreset']);
        $group->get('/{id}', [$controller, 'show']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })
        ->add(new PermissionMiddleware($authz, 'forms:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
