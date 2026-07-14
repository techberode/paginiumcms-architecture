<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/conflicts.php
 * Admin prehľad konfliktov obsahu (Iterácia 3). Auto-discovered z bootstrap/app.php.
 *
 *  - GET    /api/admin/conflicts   (ADMIN / SUPER_ADMIN)
 *  - DELETE /api/admin/conflicts   (ADMIN / SUPER_ADMIN)
 */

use PaginiumCMS\Http\Controllers\Admin\ConflictController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();

    $app->group('/api/admin/conflicts', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ConflictController::class);

        $group->get('', [$controller, 'list']);
        $group->delete('', [$controller, 'clear']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
