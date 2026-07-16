<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\MessageController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(MessageController::class);
    $auth = $container->get(AuthMiddleware::class);

    $app->group('/api/admin/messages', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listMessages']);
        $group->patch('/{id}', [$controller, 'markRead']);
        $group->delete('/{id}', [$controller, 'deleteMessage']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
