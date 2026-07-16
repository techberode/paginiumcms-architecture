<?php

declare(strict_types=1);

/**
 * Debug routes – len pri APP_DEBUG=true.
 */

use PaginiumCMS\Http\Controllers\Debug\DebugController;
use Slim\App;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    if (!\PaginiumCMS\Core\Logging\Services\DebugEventLogger::isEnabled()) {
        return;
    }

    $container = RouteBootstrap::container($app);
    $controller = $container->get(DebugController::class);

    $app->post('/api/debug/client-event', [$controller, 'clientEvent']);
};
