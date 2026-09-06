<?php

declare(strict_types=1);

/**
 * Setup wizard routes (It.25) — pre-auth, CSRF-exempt POST.
 */

use PaginiumCMS\Http\Controllers\Setup\SetupController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(SetupController::class);

    $app->get('/api/setup/preflight', [$controller, 'preflight']);
    $app->get('/api/setup/status', [$controller, 'status']);
    $app->post('/api/setup/complete', [$controller, 'complete']);
};
