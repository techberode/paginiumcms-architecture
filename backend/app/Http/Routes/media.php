<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Media\MediaController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(MediaController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/media', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listMedia']);
    })
        ->add(new RoleMiddleware($authz, ['EDITOR', 'ADMIN', 'SUPER_ADMIN']))
        ->add($auth);

    $app->group('/api/media', function (RouteCollectorProxy $group) use ($controller) {
        $group->post('/upload', [$controller, 'uploadMedia']);
        $group->patch('/{path:.+}', [$controller, 'updateMedia']);
    })
        ->add(new PermissionMiddleware($authz, 'media:upload'))
        ->add($auth);

    $app->group('/api/media', function (RouteCollectorProxy $group) use ($controller) {
        $group->delete('/{path:.+}', [$controller, 'deleteMedia']);
    })
        ->add(new PermissionMiddleware($authz, 'media:delete'))
        ->add($auth);
};
