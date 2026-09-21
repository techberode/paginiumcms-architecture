<?php

declare(strict_types=1);

/**
 * Team-scoped Kanban (It.93l). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/team-kanban
 *  - GET    /api/admin/team-kanban/{teamId}
 *  - PUT    /api/admin/team-kanban/{teamId}/board
 *  - PUT    /api/admin/team-kanban/{teamId}/canned
 *  - POST   /api/admin/team-kanban/{teamId}/tickets
 *  - PUT    /api/admin/team-kanban/{teamId}/tickets/{id}
 *  - DELETE /api/admin/team-kanban/{teamId}/tickets/{id}
 *  - POST   /api/admin/team-kanban/{teamId}/tickets/{id}/notes
 */

use PaginiumCMS\Http\Controllers\Admin\TeamKanbanController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/team-kanban', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(TeamKanbanController::class);

        $group->get('', [$controller, 'teams']);
        $group->get('/{teamId}', [$controller, 'index']);
        $group->put('/{teamId}/board', [$controller, 'saveBoard']);
        $group->put('/{teamId}/canned', [$controller, 'saveCanned']);
        $group->post('/{teamId}/tickets', [$controller, 'storeTicket']);
        $group->put('/{teamId}/tickets/{id}', [$controller, 'updateTicket']);
        $group->delete('/{teamId}/tickets/{id}', [$controller, 'destroyTicket']);
        $group->post('/{teamId}/tickets/{id}/notes', [$controller, 'addNote']);
    })
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
