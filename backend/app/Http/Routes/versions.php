<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/versions.php
 * Presunuté z bootstrap/routing.php (viď poznámku v codeeditor.php).
 */

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Controllers\Admin\VersionController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;

return function (App $app): void {
    $container = $app->getContainer();

    $app->group('/api/admin/versions', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(VersionController::class);

        // Statické cesty musia byť pred parametrickými ({contentId})
        $group->get('/compare', [$controller, 'compareVersions']);
        $group->get('/stats', [$controller, 'getStats']);
        $group->post('/restore', [$controller, 'restoreVersion']);
        $group->get('/{contentId}', [$controller, 'getHistory']);
        $group->get('/{contentId}/{version}', [$controller, 'getVersion']);
        $group->delete('/{contentId}', [$controller, 'cleanupVersions']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
