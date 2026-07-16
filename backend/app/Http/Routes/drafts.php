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
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(DraftController::class);
    $auth = $container->get(AuthMiddleware::class);

    $app->group('/api/drafts', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('/{type}/{slug}', [$controller, 'load']);
        $group->put('/{type}/{slug}', [$controller, 'save']);
        $group->delete('/{type}/{slug}', [$controller, 'discard']);
    })->add($auth);
};
