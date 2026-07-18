<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\TrashController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(TrashController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/trash', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'list']);
        $group->post('/bulk-restore', [$controller, 'bulkRestore']);
        $group->post('/{id}/restore', [$controller, 'restore']);
    })
        ->add(new RoleMiddleware($authz, ['EDITOR', 'ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
