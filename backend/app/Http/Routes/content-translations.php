<?php

declare(strict_types=1);

/**
 * Assisted content translation (It.76). Auto-discovered from bootstrap/app.php.
 *
 * Distinct from It.18d catalog editor at /api/admin/translations.
 *
 *  - GET    /api/admin/content-translations/status
 *  - POST   /api/admin/content-translations/connection
 *  - POST   /api/admin/content/{type}/{slug}/translations
 *  - GET    /api/admin/content-translations/{jobId}
 *  - POST   /api/admin/content-translations/{jobId}/apply
 *  - DELETE /api/admin/content-translations/{jobId}
 */

use PaginiumCMS\Http\Controllers\Admin\ContentTranslationController;
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

    $app->group('/api/admin/content-translations', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ContentTranslationController::class);

        $group->get('/status', [$controller, 'status']);
        $group->post('/connection', [$controller, 'testConnection']);
        $group->get('/{jobId}', [$controller, 'show']);
        $group->post('/{jobId}/apply', [$controller, 'apply']);
        $group->delete('/{jobId}', [$controller, 'discard']);
    })->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));

    $app->group('/api/admin/content/{type}/{slug}/translations', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ContentTranslationController::class);
        $group->post('', [$controller, 'create']);
    })->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
