<?php

declare(strict_types=1);

/**
 * Debug routes – len pri APP_DEBUG=true.
 */

use PaginiumCMS\Http\Controllers\Debug\DebugController;
use Slim\App;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(DebugController::class);

    // Trasa vždy existuje – pri APP_DEBUG=false controller vráti 204 (žiadny spam 404 v konzole).
    $app->post('/api/debug/client-event', [$controller, 'clientEvent']);
};
