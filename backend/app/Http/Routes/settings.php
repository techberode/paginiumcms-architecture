<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/settings.php
 * Správa nastavení CMS (Iterácia 4). Auto-discovered z bootstrap/app.php.
 *
 *  - GET  /api/settings/public          (verejný – bez auth)
 *  - GET  /api/admin/settings           (ADMIN / SUPER_ADMIN)
 *  - GET  /api/admin/settings/{group}   (ADMIN / SUPER_ADMIN)
 *  - PUT  /api/admin/settings/{group}   (ADMIN / SUPER_ADMIN)
 *  - DELETE /api/admin/settings         (ADMIN / SUPER_ADMIN) – reset na predvolené
 */

use PaginiumCMS\Http\Controllers\Admin\SettingsController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();

    // Verejný výrez nastavení pre celú aplikáciu (editor, auto-save, verejný web).
    $app->get('/api/settings/public', [SettingsController::class, 'publicSettings']);

    $app->group('/api/admin/settings', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(SettingsController::class);

        $group->get('', [$controller, 'index']);
        $group->delete('', [$controller, 'reset']);
        $group->get('/{group}', [$controller, 'show']);
        $group->put('/{group}', [$controller, 'update']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
