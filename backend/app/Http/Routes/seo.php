<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Seo\SeoController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(SeoController::class);

    $app->get('/api/seo/{type}/{slug}', [$controller, 'show']);
};
