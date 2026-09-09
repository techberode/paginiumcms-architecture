<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Themes\ThemeAssetController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(ThemeAssetController::class);
    $app->get('/theme-assets/{themeId}/{path:.*}', [$controller, 'serve']);
};
