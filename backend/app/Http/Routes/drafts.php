<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/drafts.php
 * Routes auto-save konceptov (Iterácia 2). Auto-discovered z bootstrap/app.php.
 *
 * URL kontrakt (všetko vyžaduje prihlásenie):
 *  - PUT    /api/drafts/{type}/{slug}
 *  - GET    /api/drafts/{type}/{slug}
 *  - DELETE /api/drafts/{type}/{slug}
 */

use PaginiumCMS\Http\Controllers\Content\DraftController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(DraftController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    // Koncepty sú súčasť editácie obsahu → vyžadujú content:edit,
    // nie iba prihlásenie (inak by draft mohol písať aj bežný USER).
    $app->group('/api/drafts', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('/{type}/{slug}', [$controller, 'load']);
        $group->put('/{type}/{slug}', [$controller, 'save']);
        $group->delete('/{type}/{slug}', [$controller, 'discard']);
    })
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);
};
