<?php

declare(strict_types=1);

/**
 * Support Kanban (It.93l / 93l-2). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/support-kanban
 *  - PUT    /api/admin/support-kanban/board
 *  - PUT    /api/admin/support-kanban/canned
 *  - POST   /api/admin/support-kanban/tickets
 *  - PUT    /api/admin/support-kanban/tickets/{id}
 *  - DELETE /api/admin/support-kanban/tickets/{id}
 *  - POST   /api/admin/support-kanban/tickets/{id}/notes
 */

use PaginiumCMS\Http\Controllers\Admin\SupportKanbanController;
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

    $app->group('/api/admin/support-kanban', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(SupportKanbanController::class);

        $group->get('', [$controller, 'index']);
        $group->put('/board', [$controller, 'saveBoard']);
        $group->put('/canned', [$controller, 'saveCanned']);
        $group->post('/tickets', [$controller, 'storeTicket']);
        $group->put('/tickets/{id}', [$controller, 'updateTicket']);
        $group->delete('/tickets/{id}', [$controller, 'destroyTicket']);
        $group->post('/tickets/{id}/notes', [$controller, 'addNote']);
    })
        ->add(new PermissionMiddleware($authz, 'support-ticket:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
