<?php

declare(strict_types=1);

/**
 * Theme package admin API (It.67b) + Theme Studio read API (It.88a).
 * Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/themes
 *  - POST   /api/admin/themes/deactivate
 *  - POST   /api/admin/themes/rollback
 *  - GET    /api/admin/themes/starter-package/{id}
 *  - POST   /api/admin/themes/import
 *  - POST   /api/admin/themes/{id}/activate
 *  - DELETE /api/admin/themes/{id}
 *  - GET    /api/admin/themes/{id}/files   (themes:read)
 *  - GET    /api/admin/themes/{id}/file    (themes:read)
 *  - POST   /api/admin/themes/validate     (themes:edit, no persist)
 *  - POST   /api/admin/themes/preview      (themes:edit, no persist)
 *  - POST   /api/admin/themes/normalize    (themes:edit, no persist)
 *  - POST   /api/admin/themes/save         (themes:edit, persist)
 *  - GET    /api/admin/themes/{id}/thumbnail
 *  - POST   /api/admin/themes/{id}/thumbnail
 */

use PaginiumCMS\Http\Controllers\Admin\ThemesController;
use PaginiumCMS\Http\Controllers\Admin\ThemeStudioController;
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
        $studio = $container->get(ThemeStudioController::class);

        $group->get('/{id}/files', [$studio, 'listFiles']);
        $group->get('/{id}/file', [$studio, 'getFile']);
        $group->get('/{id}/thumbnail', [$studio, 'getThumbnail']);
    })->add(new PermissionMiddleware($authz, 'themes:read'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));

    $app->group('/api/admin/themes', function (RouteCollectorProxy $group) use ($container) {
        $studio = $container->get(ThemeStudioController::class);

        $group->post('/validate', [$studio, 'validate']);
        $group->post('/preview', [$studio, 'preview']);
        $group->post('/normalize', [$studio, 'normalize']);
        $group->post('/save', [$studio, 'save']);
        $group->post('/{id}/thumbnail', [$studio, 'saveThumbnail']);
    })->add(new PermissionMiddleware($authz, 'themes:edit'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));

    $app->group('/api/admin/themes', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ThemesController::class);

        $group->get('', [$controller, 'index']);
        $group->post('/deactivate', [$controller, 'deactivate']);
        $group->post('/rollback', [$controller, 'rollback']);
        $group->get('/starter-package/{id}', [$controller, 'downloadStarter']);
        $group->post('/import', [$controller, 'import']);
        $group->post('/{id}/activate', [$controller, 'activate']);
        $group->delete('/{id}', [$controller, 'uninstall']);
    })->add(new PermissionMiddleware($authz, 'settings:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
