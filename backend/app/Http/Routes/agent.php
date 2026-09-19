<?php

declare(strict_types=1);

/**
 * CMS AI assistant (It.75). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/agent/status
 *  - POST   /api/admin/agent/connection
 *  - POST   /api/admin/agent/runs
 *  - GET    /api/admin/agent/runs/{runId}
 *  - POST   /api/admin/agent/runs/{runId}/execute
 *  - POST   /api/admin/agent/runs/{runId}/cancel
 *  - POST   /api/admin/agent/proposals/{proposalId}/apply
 *  - DELETE /api/admin/agent/proposals/{proposalId}
 */

use PaginiumCMS\Http\Controllers\Admin\AgentController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/agent', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(AgentController::class);

        $group->get('/status', [$controller, 'status']);
        $group->post('/connection', [$controller, 'testConnection']);
        $group->post('/runs', [$controller, 'create']);
        $group->get('/runs/{runId}', [$controller, 'show']);
        $group->post('/runs/{runId}/execute', [$controller, 'execute']);
        $group->post('/runs/{runId}/cancel', [$controller, 'cancel']);
        $group->post('/proposals/{proposalId}/apply', [$controller, 'apply']);
        $group->delete('/proposals/{proposalId}', [$controller, 'discard']);
    })->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
