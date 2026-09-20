<?php

declare(strict_types=1);

/**
 * Public compiled HTML (It.48b). Auto-discovered from bootstrap/app.php.
 *
 *  - GET /static-html/pages/{slug}
 *  - GET /static-html/blog/{slug}
 *
 * Anonymous GET. 404 when engine.renderMode is dynamic, slug is reserved,
 * or the compiled index.html is missing. /api /admin stay on their own routes.
 */

use PaginiumCMS\Http\Controllers\PublicApi\StaticHtmlController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(StaticHtmlController::class);

    $app->get('/static-html/pages/{slug:[a-z0-9][a-z0-9-]*}', [$controller, 'page']);
    $app->get('/static-html/blog/{slug:[a-z0-9][a-z0-9-]*}', [$controller, 'article']);
};
