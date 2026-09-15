<?php

declare(strict_types=1);

/**
 * Time tracker (It.93p). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/time-entries
 *  - GET    /api/admin/time-entries/targets
 *  - POST   /api/admin/time-entries/start
 *  - POST   /api/admin/time-entries/stop
 *  - PUT    /api/admin/time-entries/{id}
 *  - DELETE /api/admin/time-entries/{id}
 */

use PaginiumCMS\Http\Controllers\Admin\TimeEntryController;
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

    $app->group('/api/admin/time-entries', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(TimeEntryController::class);

        $group->get('', [$controller, 'index']);
        $group->get('/targets', [$controller, 'targets']);
        $group->post('/start', [$controller, 'start']);
        $group->post('/stop', [$controller, 'stop']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'destroy']);
    })
        ->add(new PermissionMiddleware($authz, 'time-entry:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
