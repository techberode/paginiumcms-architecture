<?php

declare(strict_types=1);

/**
 * Shortcode definition admin API (It.67a). Auto-discovered from bootstrap/app.php.
 *
 *  - GET    /api/admin/shortcodes
 *  - GET    /api/admin/shortcodes/{name}
 *  - PUT    /api/admin/shortcodes/{name}
 *  - POST   /api/admin/shortcodes/preview
 *  - DELETE /api/admin/shortcodes/{name}
 */

use PaginiumCMS\Http\Controllers\Admin\ShortcodeController;
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

    $app->group('/api/admin/shortcodes', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ShortcodeController::class);

        $group->get('', [$controller, 'index']);
        $group->post('/preview', [$controller, 'preview']);
        $group->post('/bulk-delete', [$controller, 'bulkDelete']);
        $group->get('/{name}', [$controller, 'show']);
        $group->put('/{name}', [$controller, 'save']);
        $group->delete('/{name}', [$controller, 'delete']);
    })->add(new PermissionMiddleware($authz, 'settings:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
