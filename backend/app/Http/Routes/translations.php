<?php

declare(strict_types=1);

/**
 * Light translation file editor (It.18d).
 *
 * GET  /api/admin/translations/catalog
 * GET  /api/admin/translations/file?path=
 * POST /api/admin/translations/save
 * GET  /api/admin/translations/backups?path=
 * POST /api/admin/translations/restore
 */

use PaginiumCMS\Http\Controllers\Admin\TranslationController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/translations', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(TranslationController::class);

        $group->get('/catalog', [$controller, 'catalog']);
        $group->get('/file', [$controller, 'getFile']);
        $group->post('/save', [$controller, 'saveFile']);
        $group->post('/validate', [$controller, 'validateFile']);
        $group->get('/backups', [$controller, 'getBackups']);
        $group->post('/restore', [$controller, 'restoreBackup']);
        $group->get('/locales', [$controller, 'listLocales']);
        $group->post('/locales', [$controller, 'createLocale']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
