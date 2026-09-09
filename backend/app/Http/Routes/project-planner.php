<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\ProjectPlanner\ProjectPlanController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(ProjectPlanController::class);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/project-plans', function (RouteCollectorProxy $group) use ($controller): void {
        $group->get('', [$controller, 'index']);
        $group->get('/overview', [$controller, 'overview']);
        $group->get('/{id}', [$controller, 'show']);
    })
        ->add(new PermissionMiddleware($authz, 'project-plan:read'))
        ->add($twoFactor)
        ->add($auth);

    $app->group('/api/admin/project-plans', function (RouteCollectorProxy $group) use ($controller): void {
        $group->post('', [$controller, 'create']);
        $group->patch('/{id}', [$controller, 'update']);
        $group->post('/{id}/items', [$controller, 'addItem']);
        $group->patch('/{id}/items/{itemId}', [$controller, 'updateItem']);
        $group->delete('/{id}/items/{itemId}', [$controller, 'deleteItem']);
    })
        ->add(new PermissionMiddleware($authz, 'project-plan:manage'))
        ->add($twoFactor)
        ->add($auth);
};
