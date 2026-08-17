<?php

declare(strict_types=1);

/**
 * Public blog helpers (It.84b sidebar widgets).
 */

use PaginiumCMS\Http\Controllers\Content\BlogSidebarController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $sidebar = $container->get(BlogSidebarController::class);

    $app->get('/api/blog/sidebar', [$sidebar, 'show']);
};
