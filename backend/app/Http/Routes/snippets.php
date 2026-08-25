<?php

declare(strict_types=1);

/**
 * Reusable snippet admin API (It.81f). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/snippets
 *  - GET    /api/admin/snippets/{name}
 *  - PUT    /api/admin/snippets/{name}
 *  - POST   /api/admin/snippets/preview
 *  - DELETE /api/admin/snippets/{name}
 */

use PaginiumCMS\Http\Controllers\Admin\SnippetController;
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

    $app->group('/api/admin/snippets', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(SnippetController::class);

        $group->get('', [$controller, 'index']);
        $group->post('/preview', [$controller, 'preview']);
        $group->post('/bulk-delete', [$controller, 'bulkDelete']);
        $group->get('/{name}', [$controller, 'show']);
        $group->put('/{name}', [$controller, 'save']);
        $group->delete('/{name}', [$controller, 'delete']);
    })->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
