<?php

declare(strict_types=1);

/**
 * CMS content migration (WordPress, Grav, Jekyll, Hugo, Ghost).
 *
 * GET  /api/admin/content-migration/sources
 * POST /api/admin/content-migration/import?format=wordpress&run=0
 */

use PaginiumCMS\Http\Controllers\Admin\ContentMigrationController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/content-migration', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ContentMigrationController::class);

        $group->get('/sources', [$controller, 'listSources']);
        $group->post('/import', [$controller, 'import']);
    })
        ->add(new PermissionMiddleware($container->get(AuthorizationInterface::class), 'content:create'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
