<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/extensions.php
 * External plugins admin API (It.15). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/extensions
 *  - PUT    /api/admin/extensions/{id}/enable
 *  - PUT    /api/admin/extensions/{id}/disable
 *  - DELETE /api/admin/extensions/{id}
 */

use PaginiumCMS\Http\Controllers\Admin\ExtensionsController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/extensions', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ExtensionsController::class);

        $group->get('', [$controller, 'index']);
        $group->post('/import', [$controller, 'import']);
        $group->put('/{id}/enable', [$controller, 'enable']);
        $group->put('/{id}/disable', [$controller, 'disable']);
        $group->delete('/{id}', [$controller, 'uninstall']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
