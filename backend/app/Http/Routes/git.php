<?php

declare(strict_types=1);

/**
 * Git publish distribution admin API (It.70). Auto-discovered from bootstrap/app.php.
 *
 *  - GET  /api/admin/git/status
 *  - GET  /api/admin/git/publish/preview
 *  - POST /api/admin/git/publish
 *  - POST /api/admin/git/publish/{jobId}/retry
 */

use PaginiumCMS\Http\Controllers\Admin\GitPublishController;
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

    $app->group('/api/admin/git', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(GitPublishController::class);

        $group->get('/status', [$controller, 'status']);
        $group->get('/publish/preview', [$controller, 'preview']);
        $group->post('/publish', [$controller, 'publish']);
        $group->post('/publish/{jobId}/retry', [$controller, 'retry']);
    })->add(new PermissionMiddleware($authz, 'git:publish'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
