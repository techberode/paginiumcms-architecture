<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Feeds\FeedController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(FeedController::class);

    $app->get('/feed.xml', [$controller, 'rss']);
    $app->get('/sitemap.xml', [$controller, 'sitemap']);
};
