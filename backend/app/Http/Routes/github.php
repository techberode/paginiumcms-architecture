<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\GitHubController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();
    $controller = $container->get(GitHubController::class);
    $auth = $container->get(AuthMiddleware::class);

    $app->group('/api/admin/github', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('/status', [$controller, 'status']);
        $group->post('/export', [$controller, 'export']);
        $group->post('/import', [$controller, 'import']);
        $group->post('/sync', [$controller, 'sync']);
        $group->put('/auto-sync', [$controller, 'setAutoSync']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
