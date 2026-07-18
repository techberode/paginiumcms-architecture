<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Comments\CommentsController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(CommentsController::class);
    $auth = $container->get(AuthMiddleware::class);

    $app->get('/api/comments', [$controller, 'listPublic']);
    $app->post('/api/comments', [$controller, 'submit']);

    $app->group('/api/admin/comments', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listAdmin']);
        $group->post('/bulk-status', [$controller, 'bulkUpdateStatus']);
        $group->post('/bulk-delete', [$controller, 'bulkDelete']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'delete']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
