<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/locking.php
 * Routes systému zamykania obsahu (Iterácia 1).
 * Auto-discovered z backend/bootstrap/app.php.
 *
 * URL kontrakt:
 *  - POST   /api/locks/acquire      (auth)         – získať zámok
 *  - POST   /api/locks/heartbeat    (auth)         – predĺžiť zámok (30 s interval)
 *  - POST   /api/locks/release      (auth)         – uvoľniť zámok
 *  - GET    /api/locks              (auth + admin) – zoznam zámkov pre dashboard
 *  - DELETE /api/locks/{resourceId} (auth + admin) – vynútené uvoľnenie
 */

use PaginiumCMS\Http\Controllers\Locking\LockController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(LockController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    // === Blok: Používateľské operácie so zámkami (editori obsahu) ===
    // Zámky slúžia na editáciu obsahu, preto vyžadujú content:edit –
    // bežný prihlásený USER nesmie držať zámky ani blokovať editorov.
    $app->group('/api/locks', function (RouteCollectorProxy $group) use ($controller) {
        $group->post('/acquire', [$controller, 'acquire']);
        $group->post('/heartbeat', [$controller, 'heartbeat']);
        $group->post('/release', [$controller, 'release']);
    })
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);

    // === Blok: Admin operácie (zoznam + vynútené uvoľnenie) ===
    $app->group('/api/locks', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listLocks']);
        $group->delete('/{resourceId}', [$controller, 'forceRelease']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
