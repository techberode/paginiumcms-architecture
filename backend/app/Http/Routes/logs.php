<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\LogController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(LogController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/logs', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('/stats', [$controller, 'stats']);
        $group->get('', [$controller, 'list']);
        $group->post('/purge', [$controller, 'purge']);
    })
        ->add(new RoleMiddleware($authz, ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
