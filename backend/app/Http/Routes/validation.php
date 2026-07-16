<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/validation.php
 * Export zdieľaných validačných pravidiel (Iterácia 4). Auto-discovered.
 *
 *  - GET /api/validation/rules
 *  - GET /api/validation/rules/{context}
 */

use PaginiumCMS\Http\Controllers\Validation\ValidationController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/validation', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(ValidationController::class);

        $group->get('/rules', [$controller, 'index']);
        $group->get('/rules/{context}', [$controller, 'show']);
    });
};
