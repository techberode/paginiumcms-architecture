<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/jobs.php
 * Admin job scheduler CRUD + run (Iteration 29).
 */

use PaginiumCMS\Http\Controllers\Admin\JobsController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/jobs', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(JobsController::class);

        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'create']);
        $group->post('/run-due', [$controller, 'runDue']);
        $group->post('/queue/process', [$controller, 'processQueue']);
        $group->get('/{id}', [$controller, 'show']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'delete']);
        $group->post('/{id}/run', [$controller, 'run']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
