<?php

declare(strict_types=1);

/**
 * Theme package admin API (It.67b). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/themes
 *  - GET    /api/admin/themes/starter-package/{id}
 *  - POST   /api/admin/themes/import
 *  - DELETE /api/admin/themes/{id}
 */

use PaginiumCMS\Http\Controllers\Admin\ThemesController;
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

    $app->group('/api/admin/themes', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ThemesController::class);

        $group->get('', [$controller, 'index']);
        $group->get('/starter-package/{id}', [$controller, 'downloadStarter']);
        $group->post('/import', [$controller, 'import']);
        $group->delete('/{id}', [$controller, 'uninstall']);
    })->add(new PermissionMiddleware($authz, 'settings:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
